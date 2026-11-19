<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f9fafb; margin: 0; padding: 24px; color: #111827; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e5e7eb; }
        .header { background: #000000; color: #ffffff; padding: 32px 24px; text-align: center; }
        .logo { font-size: 28px; font-weight: 900; letter-spacing: -1px; margin: 0; }
        .content { padding: 32px 24px; }
        .badge { display: inline-block; padding: 6px 14px; border-radius: 9999px; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-paid { background: #dbeafe; color: #1e40af; }
        .badge-processing { background: #e0e7ff; color: #3730a3; }
        .badge-shipped { background: #ede9fe; color: #5b21b6; }
        .badge-delivered { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .order-meta { background: #f9fafb; border-radius: 12px; padding: 16px 20px; margin: 24px 0; border: 1px solid #f3f4f6; }
        .order-meta table { width: 100%; border-collapse: collapse; }
        .order-meta td { padding: 6px 0; font-size: 14px; }
        .order-meta td.label { color: #6b7280; font-weight: 500; }
        .order-meta td.val { text-align: right; font-weight: 700; color: #111827; }
        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items-table th { text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
        .items-table td { padding: 12px 0; font-size: 14px; border-bottom: 1px solid #f3f4f6; }
        .footer { background: #f9fafb; padding: 20px 24px; text-align: center; font-size: 13px; color: #6b7280; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="logo">SHOP.CO</h1>
            <p style="margin: 8px 0 0; color: #9ca3af; font-size: 14px;">Order Status Notification</p>
        </div>
        <div class="content">
            <h2 style="font-size: 20px; margin-top: 0;">Hello, {{ $order->shipping_name }}</h2>
            <p style="color: #4b5563; line-height: 1.6;">
                The status of your order <strong>#{{ $order->order_number }}</strong> has been updated.
            </p>

            <div style="text-align: center; margin: 24px 0;">
                <span class="badge badge-{{ $order->status }}">
                    Status: {{ ucfirst($order->status) }}
                </span>
            </div>

            <div class="order-meta">
                <table>
                    <tr>
                        <td class="label">Order Number:</td>
                        <td class="val">#{{ $order->order_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Payment Status:</td>
                        <td class="val">{{ ucfirst($order->payment_status) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Total Amount:</td>
                        <td class="val">${{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Shipping To:</td>
                        <td class="val">{{ $order->shipping_address }}, {{ $order->shipping_city }}</td>
                    </tr>
                </table>
            </div>

            @if($order->items && count($order->items) > 0)
                <h3 style="font-size: 15px; margin: 24px 0 12px;">Order Summary</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->product_name }}</strong>
                                    @if($item->size || $item->color)
                                        <div style="font-size: 12px; color: #6b7280;">
                                            {{ $item->size ? 'Size: ' . $item->size : '' }} {{ $item->color ? '| Color: ' . $item->color : '' }}
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align: center;">{{ $item->quantity }}</td>
                                <td style="text-align: right;">${{ number_format($item->price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <p style="color: #6b7280; font-size: 14px; margin-top: 24px;">
                If you have any questions about your order, simply reply to this email or contact support.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} SHOP.CO. All rights reserved.
        </div>
    </div>
</body>
</html>
