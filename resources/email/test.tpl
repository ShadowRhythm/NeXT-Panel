{include file='components/header.tpl'}
{include file='components/hero.tpl' hero_title=('email.test_email_title'|trans)}
<p style="margin:0;">{trans key='email.test_email_desc'}</p>
{$rows = [['label' => 'UTC / Server Time', 'value' => "{$smarty.now|date_format:'Y-m-d H:i:s'}"]]}
{include file='components/card.tpl' card_rows=$rows}
{include file='components/footer.tpl'}
