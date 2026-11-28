{include file='components/header.tpl' preheader=$title|default:''}
{include file='components/hero.tpl' hero_title=$title|default:$config['appName']}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #e6e7e9;border-radius:6px;border-collapse:separate;margin:6px 0 0;">
    <tr>
        <td style="padding:14px 16px;font-family:-apple-system,'Segoe UI','PingFang SC','Microsoft YaHei',Helvetica,Arial,sans-serif;font-size:14px;line-height:22px;color:#1f2937;">{$text}</td>
    </tr>
</table>
{include file='components/footer.tpl'}
