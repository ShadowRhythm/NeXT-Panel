<!DOCTYPE html>
<html lang="{$current_locale|replace:'_':'-'}"{if isset($is_rtl) && $is_rtl} dir="rtl"{/if}>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <title>{$config['appName']}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;-webkit-text-size-adjust:100%;">
{if isset($preheader)}<div style="display:none;font-size:1px;color:#f4f5f7;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{$preheader}</div>{/if}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f5f7;">
    <tr>
        <td align="center" valign="top" style="padding:0 10px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:640px;">
                <tr>
                    <td style="background-color:#206bc4;border-radius:0 0 10px 10px;padding:0 20px;">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td align="left" style="padding:20px 6px 14px;font-family:-apple-system,'Segoe UI','PingFang SC','Microsoft YaHei',Helvetica,Arial,sans-serif;font-size:16px;font-weight:700;color:#ffffff;letter-spacing:0.2px;">
                                    {$config['appName']}
                                </td>
                            </tr>
                        </table>
