@extends('layouts.app')
@section('title', 'Order History')
@section('content')

<h3 class="mb-4 fw-bold">Order History</h3>
<div class="card p-4 mb-4">
    <label class="form-label fw-semibold">Customer Email</label>
    <div class="input-group" style="max-width: 440px;">
        <input type="email" class="form-control" id="search_email_input" placeholder="customer@example.com">
        <button class="btn btn-primary" id="search_orders_button">Search</button>
    </div>
    <div class="form-text">Enter a customer email to view their order history.</div>
</div>

<div id="order_results_container" class="text-muted">
    Enter an email to see order history.
</div>

<script>
    function format_order_date(raw_date) {
        var date_obj = new Date(raw_date);
        if (isNaN(date_obj.getTime())) return raw_date;
        return date_obj.toLocaleString('en-IN',{
            day:'2-digit',
            month:'short',
            year:'numeric',
            hour:'2-digit',
            minute:'2-digit'
        });
    }

    function get_order_line_items(order_obj) {
        return order_obj.order_lines || order_obj.orderLines || [];
    }

    function show_order_receipt(order_obj) {
        var line_items = get_order_line_items(order_obj);
        var rows_html = '';

        for (var i = 0; i < line_items.length; i++) {
            var line = line_items[i];
            var product = line.product || {};
            var product_name = product.name || ('Product #' + line.product_id);
            var qty = line.quantity;
            var unit_price = parseFloat(line.price !== undefined ? line.price : line.unit_price !== undefined ? line.unit_price : product.price) || 0;
            var line_total = line.line_total !== undefined ? parseFloat(line.line_total) : (unit_price * qty);
            
            rows_html +=
                '<tr>' +
                '<td>' + product_name + '</td>' +
                '<td>\u20B9' + unit_price.toFixed(2) + '</td>' +
                '<td>' + qty + '</td>' +
                '<td>\u20B9' + line_total.toFixed(2) + '</td>' +
                '</tr>';
        }

        if (line_items.length === 0) {
            rows_html = '<tr><td colspan="4" class="text-center text-muted">No line items on this order.</td></tr>';
        }

        var receipt_html =
            '<div class="d-flex justify-content-between border-bottom pb-2 mb-3 small text-muted">' +
                '<span>Order #' + order_obj.id + '</span>' +
                '<span>' + format_order_date(order_obj.created_at) + '</span>' +
            '</div>' +
            '<table class="table table-sm">' +
                '<thead><tr>' +
                    '<th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th>' +
                '</tr></thead>' +
                '<tbody>' + rows_html + '</tbody>' +
            '</table>' +
            '<div class="text-end fw-bold fs-5 border-top pt-2">Grand Total: \u20B9' + order_obj.grand_total + '</div>';

        Swal.fire({
            title:'Order Receipt',
            html:receipt_html,
            width:560,
            confirmButtonText:'Close',
            confirmButtonColor:'#0d6efd'
        });
    }

    function search_order_history() {
        var email_value = document.getElementById('search_email_input').value.trim();
        var results_container = document.getElementById('order_results_container');

        if (!email_value) {
            notifyError('Email required', 'Enter a customer email to search.');
            return;
        }

        results_container.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Searching...';

        fetch('/api/orders/history?email=' + encodeURIComponent(email_value), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (response) {
            return response.json().then(function (response_data) {
                return { status: response.status, body: response_data };
            });
        })
        .then(function (result) {
            if (result.status === 404) {
                toast('info', result.body.message || 'No customer found.');
                results_container.innerHTML = '<span class="text-muted">No customer found with that email.</span>';
                return;
            }

            if (result.status !== 200) {
                notifyError('Something went wrong', result.body.message || 'Please try again.');
                results_container.innerHTML = '<span class="text-muted">Enter an email to see order history.</span>';
                return;
            }

            var order_list = result.body.data || [];

            if (order_list.length === 0) {
                toast('info', 'No orders found for this customer.');
                results_container.innerHTML = '<span class="text-muted">No orders found for this customer.</span>';
                return;
            }

            var table_html =
                '<div class="card">' +
                '<div class="table-responsive">' +
                '<table class="table table-hover mb-0">' +
                '<thead><tr>' +
                    '<th>Order #</th><th>Date</th><th>Items</th><th>Grand Total</th><th class="text-center">Receipt</th>' +
                '</tr></thead>' +
                '<tbody>';

            for (var i = 0; i < order_list.length; i++) {
                var order = order_list[i];
                var line_count = get_order_line_items(order).length;

                table_html +=
                    '<tr>' +
                    '<td>#' + order.id + '</td>' +
                    '<td>' + format_order_date(order.created_at) + '</td>' +
                    '<td>' + line_count + ' item' + (line_count === 1 ? '' : 's') + '</td>' +
                    '<td>\u20B9' + order.grand_total + '</td>' +
                    '<td class="text-center">' +
                        '<button class="btn btn-sm btn-outline-primary view_receipt_button" data-index="' + i + '">' +
                            'View' +
                        '</button>' +
                    '</td>' +
                    '</tr>';
            }

            table_html += '</tbody></table></div></div>';
            results_container.innerHTML = table_html;

            var receipt_buttons = results_container.querySelectorAll('.view_receipt_button');
            for (var j = 0; j < receipt_buttons.length; j++) {
                receipt_buttons[j].addEventListener('click', function () {
                    var order_index = parseInt(this.dataset.index);
                    show_order_receipt(order_list[order_index]);
                });
            }
        })
        .catch(function (error) {
            notifyError('Request failed', error.message);
            results_container.innerHTML = '<span class="text-muted">Enter an email to see order history.</span>';
        });
    }

    document.getElementById('search_orders_button').addEventListener('click', function () {
        search_order_history();
    });

    document.getElementById('search_email_input').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') search_order_history();
    });
</script>

@endsection