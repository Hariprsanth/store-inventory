<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed</title>
</head>
<body style="margin:0; padding:0; background-color:#F4F5F7; font-family:Arial, Helvetica, sans-serif;">

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F4F5F7; padding:30px 0;">
    <tr>
      <td align="center">

        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF; border-radius:10px; overflow:hidden; border:1px solid #E5E7EB;">

          <tr>
            <td style="background-color:#1F2937; padding:28px 32px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="color:#FFFFFF; font-size:20px; font-weight:bold;">
                    {{ config('app.name') }}
                  </td>
                  <td align="right" style="color:#9CA3AF; font-size:13px;">
                    Order #{{ $order->id }}
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:32px 32px 0;">
              <table cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background-color:#ECFDF3; border-radius:6px; padding:6px 14px;">
                    <span style="color:#16A34A; font-size:13px; font-weight:bold;">✓ ORDER CONFIRMED</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Greeting -->
          <tr>
            <td style="padding:20px 32px 4px;">
              <p style="margin:0; font-size:18px; color:#111827; font-weight:bold;">
                Hi {{ $order->customer->name }},
              </p>
              <p style="margin:6px 0 0; font-size:14px; color:#6B7280; line-height:1.6;">
                Thanks for your order! Here's a summary of what you purchased.
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:20px 32px 0;">
              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <thead>
                  <tr>
                    <td style="padding:10px 0; border-bottom:2px solid #E5E7EB; font-size:12px; color:#9CA3AF; text-transform:uppercase;">Item</td>
                    <td align="center" style="padding:10px 0; border-bottom:2px solid #E5E7EB; font-size:12px; color:#9CA3AF; text-transform:uppercase;">Qty</td>
                    <td align="right" style="padding:10px 0; border-bottom:2px solid #E5E7EB; font-size:12px; color:#9CA3AF; text-transform:uppercase;">Total</td>
                  </tr>
                </thead>
                <tbody>
                  @foreach($order->orderLines as $line)
                  <tr>
                    <td style="padding:12px 0; border-bottom:1px solid #F3F4F6; font-size:14px; color:#111827;">
                      {{ $line->product->name }}
                    </td>
                    <td align="center" style="padding:12px 0; border-bottom:1px solid #F3F4F6; font-size:14px; color:#6B7280;">
                      {{ $line->quantity }}
                    </td>
                    <td align="right" style="padding:12px 0; border-bottom:1px solid #F3F4F6; font-size:14px; color:#111827; font-weight:bold;">
                      ₹{{ number_format($line->line_total, 2) }}
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:20px 32px 0;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:4px 0; font-size:13px; color:#6B7280;">Subtotal</td>
                  <td align="right" style="padding:4px 0; font-size:13px; color:#111827;">₹{{ number_format($order->subtotal, 2) }}</td>
                </tr>
                <tr>
                  <td style="padding:4px 0; font-size:13px; color:#6B7280;">Tax</td>
                  <td align="right" style="padding:4px 0; font-size:13px; color:#111827;">₹{{ number_format($order->tax_total, 2) }}</td>
                </tr>
                <tr>
                  <td style="padding:12px 0 0; font-size:16px; color:#111827; font-weight:bold; border-top:2px solid #E5E7EB;">Grand Total</td>
                  <td align="right" style="padding:12px 0 0; font-size:16px; color:#16A34A; font-weight:bold; border-top:2px solid #E5E7EB;">₹{{ number_format($order->grand_total, 2) }}</td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:28px 32px;">
              <table cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background-color:#1F2937; border-radius:6px;">
                    <a href="#" style="display:inline-block; padding:12px 24px; font-size:14px; color:#FFFFFF; text-decoration:none; font-weight:bold;">
                      View Order Details
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:20px 32px 28px; border-top:1px solid #F3F4F6;">
              <p style="margin:0; font-size:12px; color:#9CA3AF; line-height:1.6;">
                This is an automated email from {{ config('app.name') }}. If you have any questions, reply to this email.
              </p>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>