{strip}
    {assign var="pageTitle" value="plugins.importexport.datacite.displayName"}
    {include file="common/header.tpl"}
{/strip}
<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function () {ldelim}
		$('#importExportTabs').pkpHandler('$.pkp.controllers.TabHandler');
		$('#importExportTabs').tabs('option', 'cache', true);
        {rdelim});
</script>
<div id="importExportTabs" class="pkp_controllers_tab">
    <ul>
        <li><a href="#queue-tab">{translate key="plugins.importexport.datacite.queued"}</a></li>
        <li><a href="#deposited-tab">{translate key="plugins.importexport.datacite.deposited"}</a></li>
        <li><a href="#settings-tab">{translate key="plugins.importexport.datacite.settings"}</a></li>
    </ul>
    <div id="settings-tab">
        <script type="text/javascript">
			$(function () {ldelim}
				$('#dataciteSettingsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
                {rdelim});
        </script>
        <form class="pkp_form" id="dataciteSettingsForm" method="post"
              action="{plugin_url path="settings" verb="save"}">
            {if $doiPluginSettingsLinkAction}
                {fbvFormArea id="doiPluginSettingsLink"}
                {fbvFormSection}
                    {include file="linkAction/linkAction.tpl" action=$doiPluginSettingsLinkAction}
                {/fbvFormSection}
                {/fbvFormArea}
            {/if}
            {fbvFormArea id="dataciteSettingsFormArea"}
                <p class="pkp_help">{translate key="plugins.importexport.datacite.settings.description"}</p>
                <p class="pkp_help">{translate key="plugins.importexport.datacite.intro"}</p>
			{fbvFormSection list="true"}
			{fbvElement type="checkbox" id="daraMode" label="plugins.importexport.datacite.settings.form.dara" checked=$daraMode|compare:true}
			{/fbvFormSection}
			{fbvFormSection}
            {fbvElement type="text" id="api" value=$api label="plugins.importexport.datacite.settings.form.url" maxlength="100" size=$fbvStyles.size.MEDIUM}
            {fbvElement type="text" id="username" value=$username label="plugins.importexport.datacite.settings.form.username" maxlength="50" size=$fbvStyles.size.MEDIUM}
            {fbvElement type="text" password="true" id="password" value=$password label="plugins.importexport.datacite.settings.form.password" maxLength="50" size=$fbvStyles.size.MEDIUM}
                <span class="instruct">{translate key="plugins.importexport.datacite.settings.form.password.description"}</span>
                <br/>
            {/fbvFormSection}
                <hr>
            {fbvFormSection list="true"}
            {fbvElement type="checkbox" id="testMode" label="plugins.importexport.datacite.settings.form.testMode.description" checked=$testMode|compare:true}
            {/fbvFormSection}
            {fbvElement type="text" id="testRegistry" value=$testRegistry label="plugins.importexport.datacite.settings.form.testRegistry" maxlength="200" size=$fbvStyles.size.MEDIUM}
            {fbvElement type="text" id="testPrefix" value=$testPrefix label="plugins.importexport.datacite.settings.form.testPrefix" maxlength="10" size=$fbvStyles.size.MEDIUM}
            {fbvElement type="text" id="testUrl" value=$testUrl label="plugins.importexport.datacite.settings.form.testUrl" maxlength="200" size=$fbvStyles.size.MEDIUM}
            {/fbvFormArea}
            {fbvFormButtons submitText="common.save"}
        </form>
    </div>
    <div id="queue-tab">
        <script type="text/javascript">
			$(function () {ldelim}
				$('#queueXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
                {rdelim});
        </script>
        <div class="listing" width="100%">
            <form id="queueXmlForm" class="pkp_form" action="{plugin_url path="export"}" method="post">
                {csrf}
                <div class="pkp_content_panel">
                    <div class="pkpListPanel pkpListPanel--submissions">
                        <div class="pkpListPanel__body -pkpClearfix pkpListPanel__body--submissions">
                            <div class="pkpListPanel__content pkpListPanel__content--submissions">
                                <ul aria-live="polite" class="pkpListPanel__items">
                                    {foreach $itemsQueue as $key=>$item}
                                        <li class="pkpListPanelItem pkpListPanelItem--submission pkpListPanelItem--hasSummary">
                                            <div class="pkpListPanelItem__summary -pkpClearfix">
                                                <div class="pkpListPanelItem--submission__item">
                                                        <div class="pkpListPanelItem--submission__id">{$item["id"]}</div>
                                                    <div class="pkpListPanelItem--submission__reviewerWorkflowLink"><span
                                                                class="-screenReader">ID</span>

                                                    </div>
                                                    <div class="pkpListPanelItem--submission__author">
                                                        {$item["authors"]}
                                                    </div>
                                                    <div class="pkpListPanelItem--submission__title">
                                                        {$item["title"]}
                                                    </div>
                                                </div>
                                                <div class="pkpListPanelItem--submission__stage">
                                                    <div class="pkpListPanelItem--submission__stageRow">
                                                        <button class="pkpBadge pkpBadge--button pkpBadge--dot pkpBadge--submission">
                                                            <a href="{$plugin}/export?submission={$item["id"]}" class="">
                                                                {$item["pubId"]}
                                                            </a>

                                                        </button>
                                                        <div aria-hidden="true"
                                                             class="pkpListPanelItem--submission__flags">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    {/foreach}
                                </ul>
                            </div>
                        </div>
                        <div class="pkpListPanel__footer -pkpClearfix">
                            <div class="pkpListPanel__count">
                                {$itemsSizeQueue} submissions
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
    <div id="deposited-tab">
        <script type="text/javascript">
			$(function () {ldelim}
				$('#depositedXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
                {rdelim});
        </script>
        <div class="listing" width="100%">
            <form id="depositedXmlForm" class="pkp_form" action="{plugin_url path="export"}" method="post">
                {csrf}
                <div class="pkp_content_panel">
                    <div class="pkpListPanel pkpListPanel--submissions">
                        <div class="pkpListPanel__body -pkpClearfix pkpListPanel__body--submissions">
                            <div class="pkpListPanel__content pkpListPanel__content--submissions">
                                <ul aria-live="polite" class="pkpListPanel__items">
                                    {foreach $itemsDeposited as $key=>$item}
                                        <li class="pkpListPanelItem pkpListPanelItem--submission pkpListPanelItem--hasSummary">
                                            <div class="pkpListPanelItem__summary -pkpClearfix">
                                                <div class="pkpListPanelItem--submission__item">
                                                    <a href="{$plugin}/export?submission={$item["id"]}" class=""><div class="pkpListPanelItem--submission__id"><span aria-hidden="false" class="fa fa-angle-up"></span></div></a>
                                                    <div class="pkpListPanelItem--submission__reviewerWorkflowLink"><span
                                                                class="-screenReader">ID</span>
                                                        {$item["id"]}
                                                    </div>
                                                    <div class="pkpListPanelItem--submission__author">
                                                        {$item["authors"]}
                                                    </div>
                                                    <div class="pkpListPanelItem--submission__title">
                                                        {$item["title"]}
                                                    </div>
                                                </div>
                                                <div class="pkpListPanelItem--submission__stage">
                                                    <div class="pkpListPanelItem--submission__stageRow">
                                                        <button class="pkpBadge pkpBadge--button pkpBadge--dot pkpBadge--production">
                                                            <a href="{$item["registry"]}/{$item["pubId"]}" class="">
                                                                {$item["pubId"]}
                                                            </a>
                                                        </button>
                                                        <div aria-hidden="true"
                                                             class="pkpListPanelItem--submission__flags">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    {/foreach}
                                </ul>
                            </div>
                        </div>
                        <div class="pkpListPanel__footer -pkpClearfix">
                            <div class="pkpListPanel__count">
                                {$itemsSizeDeposited} submissions
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>
{include file="common/footer.tpl"}
