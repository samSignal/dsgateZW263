<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>{{ $titleText }}</title>
  </head>
  <body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
            <tr>
              <td style="padding:20px 20px;background:#064e3b;color:#ffffff;">
                <div style="font-size:14px;opacity:0.9;">DestinyGate Institute</div>
                <div style="font-size:18px;font-weight:700;margin-top:6px;">{{ $titleText }}</div>
              </td>
            </tr>
            <tr>
              <td style="padding:20px;">
                <div style="font-size:14px;line-height:1.6;color:#0f172a;">{{ $bodyText }}</div>
                <div style="margin-top:18px;">
                  <a href="{{ $buttonUrl }}" style="display:inline-block;background:#065f46;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:10px;font-size:14px;font-weight:700;">
                    {{ $buttonText }}
                  </a>
                </div>
                <div style="margin-top:16px;font-size:12px;line-height:1.5;color:#475569;">
                  {{ $expiresText }}
                </div>
                <div style="margin-top:16px;font-size:12px;line-height:1.5;color:#64748b;">
                  For your security, do not share this email or link. If you did not request this, you can ignore this message.
                </div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>

