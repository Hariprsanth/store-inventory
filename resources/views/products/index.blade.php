@extends('layouts.app')
@section('title', 'Products')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0 fw-bold">Product Catalog</h3>
    <button class="btn btn-success" id="add_product_button">+ Add Product</button>
</div>

<div class="card p-4 mb-4 d-none" id="product_form_panel">
    <h5 class="mb-3" id="form_heading">Add Product</h5>
    <input type="hidden" id="hidden_product_id_input">

    <div class="row g-3 mb-3">
        <div class="col-md">
            <label class="form-label fw-semibold">Name</label>
            <input class="form-control" id="name_input" placeholder="Product name">
        </div>
        <div class="col-md">
            <label class="form-label fw-semibold">Code <span class="text-muted fw-normal">(auto)</span></label>
            <input class="form-control" id="code_input" placeholder="Auto-generated" disabled>
        </div>
        <div class="col-md">
            <label class="form-label fw-semibold">Price (₹)</label>
            <input class="form-control" id="price_input" type="number" step="0.01" placeholder="0.00">
        </div>
        <div class="col-md">
            <label class="form-label fw-semibold">Tax %</label>
            <input class="form-control" id="tax_input" type="number" step="0.01" placeholder="0.00">
        </div>
        <div class="col-md">
            <label class="form-label fw-semibold">Stock</label>
            <input class="form-control" id="stock_input" type="number" placeholder="0">
        </div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" id="save_product_button">Save</button>
        <button class="btn btn-outline-secondary" id="cancel_form_button">Cancel</button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="products_table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Tax %</th>
                    <th>Stock</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="products_table_body">
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <span class="spinner-border spinner-border-sm me-2"></span>Loading...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<nav id="products_pagination" aria-label="Product pages" class="mt-3"></nav>

<script>
    var editing_product_id = null;
    var product_list = [];
    var current_page = 1;
    var page_size = 10;

    function load_product_list() {
        var table_body = document.getElementById('products_table_body');
        fetch('/api/products', { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (response_data) {
                product_list = response_data.data || [];
                current_page = Math.min(current_page, Math.max(1, Math.ceil(product_list.length / page_size)));
                render_table_page();
            })
            .catch(function () {
                table_body.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Could not load products.</td></tr>';
                document.getElementById('products_pagination').innerHTML = '';
            });
    }

    function render_table_page() {
        var table_body = document.getElementById('products_table_body');
        var total_pages = Math.max(1, Math.ceil(product_list.length / page_size));
        current_page = Math.min(current_page, total_pages);

        if (product_list.length === 0) {
            table_body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No products found.</td></tr>';
            document.getElementById('products_pagination').innerHTML = '';
            return;
        }

        var start_index = (current_page - 1) * page_size;
        var page_items = product_list.slice(start_index, start_index + page_size);
        var rows_html = '';

        for (var i = 0; i < page_items.length; i++) {
            var product = page_items[i];
            var stock_class = product.stock === 0
                ? 'text-danger fw-bold'
                : (product.stock < 10 ? 'text-warning fw-bold' : '');

            rows_html +=
                '<tr>' +
                '<td>' + product.code + '</td>' +
                '<td>' + product.name + '</td>' +
                '<td>\u20B9' + product.price + '</td>' +
                '<td>' + product.tax_percentage + '%</td>' +
                '<td class="' + stock_class + '">' + product.stock + '</td>' +
                '<td class="text-center">' +
                    '<button class="btn btn-sm btn-outline-primary me-1 edit_product_button"' +
                        ' data-id="' + product.id + '"' +
                        ' data-name="' + product.name + '"' +
                        ' data-code="' + product.code + '"' +
                        ' data-price="' + product.price + '"' +
                        ' data-tax="' + product.tax_percentage + '"' +
                        ' data-stock="' + product.stock + '"' +
                    '>Edit</button>' +
                    '<button class="btn btn-sm btn-outline-danger delete_product_button"' +
                        ' data-id="' + product.id + '"' +
                        ' data-name="' + product.name + '"' +
                    '>Delete</button>' +
                '</td>' +
                '</tr>';
        }

        table_body.innerHTML = rows_html;
        attach_table_row_events();
        render_pagination(total_pages);
    }

    function render_pagination(total_pages) {
        var pagination_nav = document.getElementById('products_pagination');

        if (total_pages <= 1) {
            pagination_nav.innerHTML = '';
            return;
        }

        var html = '<ul class="pagination">';

        html += '<li class="page-item ' + (current_page === 1 ? 'disabled' : '') + '">' +
                '<button class="page-link" data-page="' + (current_page - 1) + '">&laquo;</button></li>';

        for (var page = 1; page <= total_pages; page++) {
            html += '<li class="page-item ' + (page === current_page ? 'active' : '') + '">' +
                    '<button class="page-link" data-page="' + page + '">' + page + '</button></li>';
        }

        html += '<li class="page-item ' + (current_page === total_pages ? 'disabled' : '') + '">' +
                '<button class="page-link" data-page="' + (current_page + 1) + '">&raquo;</button></li>';

        html += '</ul>';
        pagination_nav.innerHTML = html;

        var page_link_buttons = pagination_nav.querySelectorAll('.page-link');
        for (var i = 0; i < page_link_buttons.length; i++) {
            page_link_buttons[i].addEventListener('click', function () {
                var target_page = parseInt(this.dataset.page);
                if (target_page < 1 || target_page > total_pages || target_page === current_page) return;
                current_page = target_page;
                render_table_page();
            });
        }
    }

    function generate_product_code() {
        var existing_codes = [];
        for (var i = 0; i < product_list.length; i++) {
            existing_codes.push(product_list[i].code);
        }

        var generated_code;

        do {
            var random_num = Math.floor(1000 + Math.random() * 9000);
            generated_code = 'PRD-' + random_num;
        } while (existing_codes.indexOf(generated_code) !== -1);

        return generated_code;
    }

    document.getElementById('name_input').addEventListener('input', function () {
        if (editing_product_id) return;
        if (document.getElementById('code_input').value) return;
        document.getElementById('code_input').value = generate_product_code();
    });

    function attach_table_row_events() {
        var edit_buttons = document.querySelectorAll('.edit_product_button');
        for (var i = 0; i < edit_buttons.length; i++) {
            edit_buttons[i].addEventListener('click', function () {
                editing_product_id = this.dataset.id;

                document.getElementById('form_heading').innerText = 'Edit Product';
                document.getElementById('name_input').value = this.dataset.name;
                document.getElementById('code_input').value = this.dataset.code;
                document.getElementById('price_input').value = this.dataset.price;
                document.getElementById('tax_input').value = this.dataset.tax;
                document.getElementById('stock_input').value = this.dataset.stock;
                document.getElementById('product_form_panel').classList.remove('d-none');
            });
        }

        var delete_buttons = document.querySelectorAll('.delete_product_button');
        for (var j = 0; j < delete_buttons.length; j++) {
            delete_buttons[j].addEventListener('click', function () {
                var product_id = this.dataset.id;
                var product_name = this.dataset.name;

                confirmDanger(
                    'Delete this product?',
                    product_name + ' will be removed from the catalog.',
                    'Yes, delete it'
                ).then(function (confirmed) {
                    if (!confirmed) return;

                    fetch('/api/products/' + product_id, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(function (response) {
                        return response.json().then(function (response_data) {
                            return { status: response.status, body: response_data };
                        });
                    })
                    .then(function (result) {
                        if (result.status === 200) {
                            toast('success', 'Product deleted');
                            load_product_list();
                        } else {
                            notifyError('Could not delete product', result.body.message);
                        }
                    })
                    .catch(function (error) {
                        notifyError('Request failed', error.message);
                    });
                });
            });
        }
    }

    document.getElementById('add_product_button').addEventListener('click', function () {
        editing_product_id = null;

        document.getElementById('form_heading').innerText = 'Add Product';
        document.getElementById('name_input').value = '';
        document.getElementById('code_input').value = generate_product_code();
        document.getElementById('price_input').value = '';
        document.getElementById('tax_input').value = '';
        document.getElementById('stock_input').value = '';
        document.getElementById('product_form_panel').classList.remove('d-none');
    });

    document.getElementById('cancel_form_button').addEventListener('click', function () {
        document.getElementById('product_form_panel').classList.add('d-none');
    });

    document.getElementById('save_product_button').addEventListener('click', function () {
        var payload = {
            name: document.getElementById('name_input').value,
            code: document.getElementById('code_input').value,
            price: document.getElementById('price_input').value,
            tax_percentage: document.getElementById('tax_input').value,
            stock: document.getElementById('stock_input').value
        };

        if (!editing_product_id && !payload.code) {
            notifyError('Name required', 'Enter a name so a product code can be generated.');
            return;
        }

        var save_button = document.getElementById('save_product_button');
        var original_text = save_button.innerHTML;
        save_button.disabled = true;
        save_button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        var api_url = editing_product_id ? '/api/products/' + editing_product_id : '/api/products';
        var api_method = editing_product_id ? 'PUT' : 'POST';

        fetch(api_url, {
            method: api_method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function (response) {
            return response.json().then(function (response_data) {
                return { status: response.status, body: response_data };
            });
        })
        .then(function (result) {
            if (result.status === 200 || result.status === 201) {
                document.getElementById('product_form_panel').classList.add('d-none');
                toast('success', editing_product_id ? 'Product updated' : 'Product added');
                if (!editing_product_id) current_page = 1;
                load_product_list();
            } else if (result.status === 422) {
                var validation_errors = result.body.errors;
                var error_message = '';
                for (var field in validation_errors) {
                    error_message += validation_errors[field][0] + ' ';
                }
                notifyError('Could not save product', error_message.trim());
            } else {
                notifyError('Could not save product', result.body.message || 'Something went wrong.');
            }
        })
        .catch(function (error) {
            notifyError('Request failed', error.message);
        })
        .finally(function () {
            save_button.disabled = false;
            save_button.innerHTML = original_text;
        });
    });

    load_product_list();
</script>

@endsection