@extends('layouts.app')
@section('title', 'Low Stock')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0 fw-bold">Low Stock Products</h3>
</div>

<div class="card p-3 mb-4" style="max-width: 280px;">
    <label class="form-label fw-semibold mb-1">Stock Threshold</label>
    <input type="number" class="form-control" id="threshold_input" value="10" min="1">
    <div class="form-text">Show products with stock below this number.</div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="low_stock_table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody id="low_stock_table_body">
                <tr>
                    <td colspan="3" class="text-center text-muted py-4">
                        <span class="spinner-border spinner-border-sm me-2"></span>Loading...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    function load_low_stock_products() {
        var threshold_value = document.getElementById('threshold_input').value || 10;
        var table_body = document.getElementById('low_stock_table_body');

        table_body.innerHTML =
            '<tr><td colspan="3" class="text-center text-muted py-4">' +
            '<span class="spinner-border spinner-border-sm me-2"></span>Loading...' +
            '</td></tr>';

        fetch('/api/products/low-stock?threshold=' + encodeURIComponent(threshold_value), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (response) { return response.json(); })
        .then(function (response_data) {
            var low_stock_list = response_data.data || [];

            if (low_stock_list.length === 0) {
                toast('info', 'No low-stock products found.');
                table_body.innerHTML =
                    '<tr><td colspan="3" class="text-center text-muted py-4">No low-stock products found.</td></tr>';
                return;
            }

            var rows_html = '';
            for (var i = 0; i < low_stock_list.length; i++) {
                var product = low_stock_list[i];
                var row_class = product.stock === 0 ? 'table-danger' : 'table-warning';

                rows_html +=
                    '<tr class="' + row_class + '">' +
                    '<td>' + product.code + '</td>' +
                    '<td>' + product.name + '</td>' +
                    '<td><strong>' + product.stock + '</strong></td>' +
                    '</tr>';
            }

            table_body.innerHTML = rows_html;
        })
        .catch(function (error) {
            table_body.innerHTML =
                '<tr><td colspan="3" class="text-center text-danger py-4">Could not load low-stock products.</td></tr>';
            notifyError('Could not load low-stock products', error.message);
        });
    }

    document.getElementById('threshold_input').addEventListener('input', function () {
        load_low_stock_products();
    });

    load_low_stock_products();
</script>

@endsection