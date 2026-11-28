{include file='components/header.tpl'}
{include file='components/hero.tpl' hero_title=('email.traffic_report_title'|trans)}
{$rows = []}
{if isset($user)}
    {$rows[] = ['text' => ('email.username_label'|trans:null:['username' => $user->user_name])]}
    {$rows[] = ['label' => 'Email', 'value' => $user->email]}
{/if}
{if isset($lastday_traffic)}{$rows[] = ['text' => ('email.today_traffic'|trans:null:['traffic' => $lastday_traffic])]}{/if}
{if isset($used_traffic)}{$rows[] = ['text' => ('email.used_traffic'|trans:null:['traffic' => $used_traffic])]}{/if}
{if isset($unused_traffic)}{$rows[] = ['text' => ('email.remaining_traffic'|trans:null:['traffic' => $unused_traffic])]}{/if}
{if isset($enable_traffic)}{$rows[] = ['text' => ('email.total_traffic'|trans:null:['traffic' => $enable_traffic])]}{/if}
{if $rows}{include file='components/card.tpl' card_rows=$rows}{/if}
{if isset($used_pct)}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:4px 0 16px;">
    <tr>
        <td style="background-color:#e9ecef;border-radius:6px;height:10px;line-height:10px;font-size:0;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="{$used_pct}%" style="max-width:100%;">
                <tr>
                    <td bgcolor="{if $used_pct >= 80}#c62828{else}#206bc4{/if}" style="height:10px;line-height:10px;font-size:0;border-radius:6px;">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right" style="padding-top:4px;font-family:-apple-system,'Segoe UI','PingFang SC','Microsoft YaHei',Helvetica,Arial,sans-serif;font-size:12px;color:#667085;">{$used_pct}%</td>
    </tr>
</table>
{/if}
<p style="margin:8px 0 0;font-size:14px;line-height:22px;color:#475467;">{$text}</p>
{include file='components/button.tpl' btn_text=$config['appName'] btn_url="{$config['baseUrl']}/user"}
{include file='components/footer.tpl'}
