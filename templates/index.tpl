
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
		<li><a href="#export-tab">{translate key="plugins.importexport.datacite.export"}</a></li>
		<li><a href="#settings-tab">{translate key="plugins.importexport.datacite.settings"}</a></li>
	</ul>
	<div id="settings-tab">
		<script type="text/javascript">
			$(function() {ldelim}
				// Attach the form handler.
				$('#dataciteSettingsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
				{rdelim});
		</script>
		<form class="pkp_form" id="dataciteSettingsForm" method="post" action="{plugin_url path="settings" verb="save"}">
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
			{fbvFormSection}
			{fbvElement type="text" id="username" value=$username label="plugins.importexport.datacite.settings.form.username" maxlength="50" size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" password="true" id="password" value=$password label="plugins.importexport.datacite.settings.form.password" maxLength="50" size=$fbvStyles.size.MEDIUM}
				<span class="instruct">{translate key="plugins.importexport.datacite.settings.form.password.description"}</span><br/>
			{/fbvFormSection}

			{fbvFormSection list="true"}
			{fbvElement type="checkbox" id="testMode" label="plugins.importexport.datacite.settings.form.testMode.description" checked=$testMode|compare:true}
			{/fbvFormSection}
			{/fbvFormArea}
			{fbvFormButtons submitText="common.save"}

		</form>

	</div>
	<div id="export-tab">

		<script type="text/javascript">
			$(function () {ldelim}
				// Attach the form handler.
				$('#exportXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
				{rdelim});
		</script>
		<form id="exportXmlForm" class="pkp_form" action="{plugin_url path="export"}" method="post">
			{csrf}
			{fbvFormArea id="exportForm"}
			{fbvFormSection}
			{assign var="uuid" value=""|uniqid|escape}
				<div id="export-submissions-list-handler-{$uuid}">
					<script type="text/javascript">
						pkp.registry.init('export-submissions-list-handler-{$uuid}', 'SelectSubmissionsListPanel', {$exportSubmissionsListData});
					</script>
				</div>
			{/fbvFormSection}
			{fbvFormButtons submitText="plugins.importexport.datacite.export" hideCancel="true"}
			{/fbvFormArea}
		</form>
	</div>
</div>

{include file="common/footer.tpl"}
