@extends('layouts.app')
@section('title', 'New Order')

@section('content')
<style>
    .ledger-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.07);
    }
    .totals-strip {
        background: #f8f9ff;
        border-radius: 10px;
        padding: 16px 20px;
    }
    #items_table select, #items_table input {
        transition: box-shadow .2s ease, border-color .2s ease;
    }
    #items_table select:focus, #items_table input:focus {
        box-shadow: 0 0 0 3px rgba(78,84,200,0.15);
    }
    .row-enter {
        animation: rowIn .3s ease both;
    }
    @keyframes rowIn {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .row-exit {
        transition: opacity .25s ease, transform .25s ease;
        opacity: 0;
        transform: translateX(20px);
    }
</style>

    <h3 class="mb-4 fw-bold text-primary">
        <i class="bi bi-cart-plus me-2"></i>New Order
    </h3>

    <div class="ledger-card p-4">
        <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <label class="form-label fw-semibold">Customer Name</label>
                <input type="text" class="form-control" id="customer_name_input" placeholder="Enter customer name">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Customer Email</label>
                <input type="email" class="form-control" id="customer_email_input" placeholder="customer@example.com">
            </div>
        </div>

        <h5 class="mb-3 fw-semibold"><i class="bi bi-basket3 me-1"></i>Items</h5>

        <div class="table-responsive">
            <table class="table align-middle" id="items_table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="width:140px">Quantity</th>
                        <th style="width:90px" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><select class="form-select"><option>-- select product --</option></select></td>
                        <td><input type="number" class="form-control" value="1" min="1"></td>
                        <td class="text-center"><button class="btn btn-sm btn-outline-danger">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <button class="btn btn-sm btn-outline-secondary" id="add_item_button">
            <i class="bi bi-plus-lg me-1"></i>Add Item
        </button>

        <hr class="my-4">

        <div class="totals-strip">
            <div class="row">
                <div class="col-md-6 offset-md-6">
                    <div class="d-flex justify-content-between py-1">
                        <span>Subtotal</span><strong id="subtotal_label">—</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span>Tax</span><strong id="tax_label">—</strong>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fs-5">
                        <span class="fw-semibold">Grand Total</span>
                        <strong id="grand_total_label" class="text-primary">—</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mt-4">
            <button class="btn btn-primary px-4" id="place_order_button">
                <i class="bi bi-bag-check me-1"></i>Place Order
            </button>
        </div>
    </div>

<script>
var product_list = [];

function load_products() {
    fetch('/api/products', {
         headers: { 
        'Accept': 'application/json' 
    } })
    .then(function (response) {
        return response.json();
    })
    .then(function (response_data) {
        product_list = response_data.data || [];
        var product_dropdowns = document.querySelectorAll('#items_table select');
        for (var i = 0; i < product_dropdowns.length; i++) {
            var dropdown = product_dropdowns[i];

            fill_product_dropdown(dropdown);
        }
    })
    .catch(function () {
        notifyError('Could not load products', 'Check your connection and refresh the page.');
    });
}

function fill_product_dropdown(dropdown) {
    var options_html = '<option value="">-- select product --</option>';

    for (var i = 0; i < product_list.length; i++) {
        var product = product_list[i];
        var product_id = product.id;
        var product_name = product.name;
        var product_code = product.code;
        var unit_price = product.price;
        var tax_percentage = product.tax_percentage;
        var stock_count = product.stock;

        options_html +=
            '<option value="' + product_id + '"' +
            ' data-price="' + unit_price + '"' +
            ' data-tax="' + tax_percentage + '"' +
            ' data-stock="' + stock_count + '">' +
            product_name + ' (' + product_code + ') - \u20B9' + unit_price +
            ' [stock: ' + stock_count + ']' +
            '</option>';
    }

    dropdown.innerHTML = options_html;
}

function validate_stock(row) {
    var product_dropdown = row.querySelector('select');
    var quantity_input = row.querySelector('input[type=number]');
    var selected_product = product_dropdown.selectedOptions[0];

    if (!selected_product || !selected_product.value) {
        quantity_input.classList.remove('is-invalid');
        return true;
    }

    var available_stock = parseInt(selected_product.dataset.stock);
    var requested_quantity = parseInt(quantity_input.value) || 0;
    var product_name = selected_product.textContent.split(' - ')[0];

    if (available_stock === 0) {
        notifyError('Out of stock', product_name + ' is currently out of stock.');
        quantity_input.classList.add('is-invalid');
        return false;
    }

    if (requested_quantity > available_stock) {
        notifyError('Not enough stock', product_name + ' has only ' + available_stock + ' unit(s) in stock.');
        quantity_input.value = available_stock;
        quantity_input.classList.remove('is-invalid');
        return false;
    }

    quantity_input.classList.remove('is-invalid');
    return true;
}

function update_order_totals() {
    var subtotal = 0;
    var total_tax = 0;
    var item_rows = document.querySelectorAll('#items_table tbody tr');

    for (var i = 0; i < item_rows.length; i++) {
        var row = item_rows[i];

        var product_dropdown = row.querySelector('select');
        var quantity_input = row.querySelector('input[type=number]');
        var selected_product = product_dropdown.selectedOptions[0];

        if (!selected_product || !selected_product.value) {
            continue;
        }

        var unit_price = parseFloat(selected_product.dataset.price);
        var tax_percentage = parseFloat(selected_product.dataset.tax);
        var quantity = parseInt(quantity_input.value) || 0;
        var line_subtotal = unit_price * quantity;

        subtotal += line_subtotal;
        total_tax += line_subtotal * (tax_percentage / 100);
    }

    var grand_total = subtotal + total_tax;

    document.getElementById('subtotal_label').innerText = '\u20B9' + subtotal.toFixed(2);
    document.getElementById('tax_label').innerText = '\u20B9' + total_tax.toFixed(2);
    document.getElementById('grand_total_label').innerText = '\u20B9' + grand_total.toFixed(2);
}

function attach_row_events(row) {
    var product_dropdown = row.querySelector('select');
    var quantity_input = row.querySelector('input[type=number]');
    var remove_button = row.querySelector('button');

    product_dropdown.addEventListener('change', function () {
        validate_stock(row);
        update_order_totals();
    });

    quantity_input.addEventListener('input', function () {
        validate_stock(row);
        update_order_totals();
    });

    remove_button.addEventListener('click', function () {
        var all_rows = document.querySelectorAll('#items_table tbody tr');
        if (all_rows.length > 1) {
            row.classList.add('row-exit');
            row.addEventListener('transitionend', function () {
                row.remove();
                update_order_totals();
            }, { once: true });
        }
    });
}

function add_item_row() {
    var table_body = document.querySelector('#items_table tbody');
    var first_row = table_body.rows[0];
    var new_row = first_row.cloneNode(true);

    new_row.querySelector('input[type=number]').value = 1;
    new_row.querySelector('input[type=number]').classList.remove('is-invalid');
    new_row.classList.add('row-enter');
    table_body.appendChild(new_row);

    fill_product_dropdown(new_row.querySelector('select'));
    attach_row_events(new_row);
}

function get_order_items() {
    var order_items = [];
    var item_rows = document.querySelectorAll('#items_table tbody tr');

    for (var i = 0; i < item_rows.length; i++) {
        var row = item_rows[i];

        var selected_product_id = row.querySelector('select').value;
        var selected_quantity = parseInt(row.querySelector('input[type=number]').value);

        if (selected_product_id) {
            order_items.push({
                product_id: parseInt(selected_product_id),
                quantity: selected_quantity
            });
        }
    }

    return order_items;
}

function validate_all_rows() {
    var all_valid = true;
    var item_rows = document.querySelectorAll('#items_table tbody tr');

    for (var i = 0; i < item_rows.length; i++) {
        var row = item_rows[i];

        if (!validate_stock(row)) {
            all_valid = false;
        }
    }

    return all_valid;
}

function place_order() {
    if (!validate_all_rows()) {
        return;
    }

    var order_items = get_order_items();

    if (order_items.length === 0) {
        notifyError('No items added', 'Select at least one product before placing the order.');
        return;
    }

    var customer_name = document.getElementById('customer_name_input').value;
    var customer_email = document.getElementById('customer_email_input').value;

    var order_data = {
        customer_name: customer_name,
        customer_email: customer_email,
        items: order_items
    };

    var place_order_button = document.getElementById('place_order_button');
    var original_button_text = place_order_button.innerHTML;
    place_order_button.disabled = true;
    place_order_button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Placing order...';

    fetch('/api/orders', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(order_data)
    })
        .then(function (response) {
            return response.json().then(function (response_data) {
                return { status: response.status, ok: response.ok, body: response_data };
            });
        })
        .then(function (result) {
            if (result.ok) {
                var grand_total = result.body.data.grand_total;
                notifySuccess('Order placed!', 'Grand total: \u20B9' + grand_total)
                    .then(function () {
                        location.reload();
                    });
            } else {
                notifyError('Could not place order', result.body.message || 'Something went wrong.');
            }
        })
        .catch(function (error) {
            notifyError('Request failed', error.message);
        })
        .finally(function () {
            place_order_button.disabled = false;
            place_order_button.innerHTML = original_button_text;
        });
}

function init_order_page() {
    var item_rows = document.querySelectorAll('#items_table tbody tr');
    for (var i = 0; i < item_rows.length; i++) {
        var row = item_rows[i];

        attach_row_events(row);
    }

    document.getElementById('add_item_button').addEventListener('click', function () {
        add_item_row();
    });

    document.getElementById('place_order_button').addEventListener('click', function () {
        place_order();
    });

    load_products();
}

init_order_page();
</script>
@endsection