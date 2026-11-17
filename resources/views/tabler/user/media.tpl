{include file='user/header.tpl'}
<script src="https://code.jquery.com/jquery-3.7.1.slim.min.js"></script>

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">{trans key='user.media_title'}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">{trans key='user.media_subtitle'}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter text-nowrap datatable">
                                <thead>
                                    <tr>
                                        <th>{trans key='user.node'}</th>
                                        {foreach $results['0']['unlock_item'] as $key => $value}
                                            <th>{$key}</th>
                                        {/foreach}
                                        {if $results['0']['ip_info']}
                                            {foreach $results['0']['ip_info'] as $key => $value}
                                                <th>{$key}</th>
                                            {/foreach}
                                        {/if}
                                        <th>{trans key='user.update_time'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $results as $result}
                                        <tr>
                                            <td>{$result['node_name']}</td>
                                            {foreach $result['unlock_item'] as $key => $value}
                                                <td><span class="">{$value}</span></td>
                                            {/foreach}
                                            {foreach $result['ip_info'] as $key => $value}
                                                <td><span class="badge bg-blue-lt">{$value}</span></td>
                                            {/foreach}
                                            <td>{$result.created_at|date_format:"%Y-%m-%d %H:%M:%S"}</td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
$(document).ready(function(){
    $('td > span').each(function() {
        var text = $(this).text().trim();
        if (text.indexOf('DNS') !== -1) {
            $(this).addClass('badge bg-cyan-lt');
        } else if (text === 'Yes' || text.startsWith('Yes ') || text.startsWith('Yes(')) {
            $(this).addClass('badge bg-green-lt');
        } else if (text === 'No' || text.startsWith('No ') || text.startsWith('No(')) {
            $(this).addClass('badge bg-red-lt');
        } else if (text.indexOf('Only') !== -1) {
            $(this).addClass('badge bg-purple-lt');
        } else if (text.indexOf('Failed') !== -1) {
            $(this).addClass('badge bg-yellow-lt');
        }
    });
});
</script>
{include file='user/footer.tpl'}