{extends file="layouts/backend.tpl"}

{block name="page"}
<h1 class="app__pageHeading">
	{$pageTitle|escape}
</h1>

<tabs label="Datacite XML Plugin Tabs">
	<tab label="{translate key='plugins.importexport.datacite.settings'}" id="settings-tab-head">
		<div id="settings-tab">
			<script type="text/javascript">
				$(function () {ldelim}
					$('#dataciteSettingsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
					{rdelim});
			</script>
			<form class="pkp_form" id="dataciteSettingsForm" method="post"
				  action="{plugin_url path="settings" verb="save"}">
				{csrf}
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
				{/fbvFormArea}
				{fbvFormButtons submitText="common.save"}
			</form>
		</div>
	</tab>
	<tab label="{translate key='plugins.importexport.datacite.tab.monographs'}" id="exportSubmissions-tab-head">
		<div id="exportSubmissions-tab">
			<script type="text/javascript">
				$(function () {ldelim}
					$('#queueXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler',
							{
								trackFormChanges: false
							}
					);
					{rdelim});
			</script>
			<form id="queueXmlForm" class="pkp_form" action="{plugin_url path='export'}" method="post">
				{csrf}
				<div id="datacitelistgrid" class="pkp_controllers_grid">
					<div class="header">
						<h4>{translate key='plugins.importexport.datacite.tab.monographs'}</h4>
						<ul class="actions">
							<li>
								<a href="#" id="datacite-open-search-button" title="Search"
								   class="pkp_controllers_linkAction pkp_linkaction_search pkp_linkaction_icon_search_extras_expand">
									{translate key='plugins.importexport.datacite.search'}
								</a>
								<script>
									$(function () {
										$('#datacite-open-search-button').pkpHandler(
												'$.pkp.controllers.linkAction.LinkActionHandler',
												{
													staticId: 'datacite-open-search-button',
													actionRequest: "$.pkp.classes.linkAction.NullAction",
													actionRequestOptions: {
													}
												}
										);
									});
								</script>
							</li>
						</ul>
					</div>
					<div class="pkp_form filter" id="datacite-filter-form" style="display: none;">
						<fieldset id="submissionSearchForm">
							{fbvFormSection}
								<div class="pkp_helpers_quarter inline">
									<label>
										<select name="sel-search-type" id="sel-search-type">
											<option value="title">
												{translate key='plugins.importexport.datacite.search.title'}
											</option>
											<option value="authors">
												{translate key='plugins.importexport.datacite.search.authors'}
											</option>
										</select>
									</label>
								</div>
								<div class="pkp_helpers_half inline">
									<label for="search-text"></label>
									<input type="search" class="field text" name="search-text" value="" id="search-text">
									<span></span>
								</div>
							{/fbvFormSection}
							{fbvFormSection}
								<div class="pkp_helpers_quarter inline">
									<label>
										<select name="sel-search-status" id="sel-search-status">
											<option value="all" selected="selected">
												{translate key='plugins.importexport.datacite.status.any'}
											</option>
											<option value="notDeposited">
												{translate key='plugins.importexport.datacite.status.todeposit'}
											</option>
											<option value="markedRegistered">
												{translate key='plugins.importexport.datacite.status.markedregistered'}
											</option>
											<option value="registered">
												{translate key='plugins.importexport.datacite.status.registered'}
											</option>
										</select>
									</label>
								</div>
								<div class="pkp_helpers_quarter inline"></div>
							{/fbvFormSection}
							<div class="section formButtons form_buttons ">
								<button id="datacite-search-button" class="pkp_button  submitFormButton" type="button">
									{translate key='plugins.importexport.datacite.search'}
								</button>
								<span class="pkp_spinner"></span>
							</div>
						</fieldset>
					</div>
					{fbvFormSection}
						<table id="datacitelistgrid-table" class="datacite-table">
							<colgroup>
								<col class="grid-column column-select" style="width: 5%;">
								<col class="grid-column column-id" style="width: 5%;">
								<col class="grid-column column-title" style="width: 40%;">
								<col class="grid-column column-issue" style="width: 10%;">
								<col class="grid-column column-pubId" style="width: 20%;">
								<col class="grid-column column-status" style="width: 20%;">
							</colgroup>
							<thead>
							<tr>
								<th scope="col" style="text-align: right;">
									{translate key='plugins.importexport.datacite.table.select'}
								</th>
								<th scope="col" style="text-align: left;">
									{translate key='plugins.importexport.datacite.table.id'}
								</th>
								<th scope="col" style="text-align: left;">
									{translate key='plugins.importexport.datacite.table.authortitle'}
								</th>
								<th scope="col" style="text-align: left;">
									{translate key='plugins.importexport.datacite.table.published'}
								</th>
								<th scope="col" style="text-align: left;">
									{translate key='plugins.importexport.datacite.table.doi'}
								</th>
								<th scope="col" style="text-align: left;">
									{translate key='plugins.importexport.datacite.table.status'}
								</th>
							</tr>
							</thead>
							<tbody>
							{foreach $itemsQueue as $key=>$item}
								<tr id="datacitelistgrid-row-{$key}" class="gridRow has_extras datacite-row
								{if $key < $endItem }
									datacite-show-row
								{else}
									datacite-hide-row
								{/if}
									">
									<td class="first_column">
										<a href="#" class="show_extras dropdown-{$item['id']}{if $item['chapters']|@count < 1} datacite-hidden{/if}"></a>
										<label for="select-{$item['id']}"></label>
										<input type="checkbox" id="select-{$item['id']}" name="selectedSubmissions[]" style="height: 15px; width: 15px;" value="{$item['id']}" class="submissionCheckbox"
												{if empty($item['doi']) || $item['status'] === 'markedRegistered'}
													disabled
												{/if}
										>
									</td>
									<td class=" pkp_helpers_text_left">
									<span id="cell-{$item['id']}-id" class="gridCellContainer">
										<span class="label before_actions">
											{$item['id']}
										</span>
									</span>
									</td>
									<td class=" pkp_helpers_text_left">
										<a href="{$item['workflow']}" target="_self"
										   class="pkpListPanelItem--submission__link">
											<div id="cell-{$item['id']}-authors"
												 class="gridCellContainer datacite-ellipsis datacite-authors">
												{$item['authors']}
											</div>
											<div id="cell-{$item['id']}-title"
												 class="gridCellContainer datacite-ellipsis">
												{$item['title']}
											</div>
										</a>
									</td>
									<td class=" pkp_helpers_text_left">
									<span id="cell-{$item['id']}-published" class="gridCellContainer">
										{$item['date']}
									</span>
									</td>
									<td class=" pkp_helpers_text_left">
									<span id="cell-{$item['id']}-pubId" class="gridCellContainer">
										<span class="label datacite-break-word">
											{if empty($item['pubId'])}
												{$item['doi']}
											{else}
												{$item['pubId']}
											{/if}
										</span>
									</span>
									</td>
									<td class=" pkp_helpers_text_left">
									<span id="cell-{$item['id']}-status" class="gridCellContainer">
										<span class="label datacite-break-word">
											{if $item['status'] === 'notDeposited'}
												{translate key='plugins.importexport.datacite.status.todeposit'}
											{elseif $item['status'] === 'registered'}
												{translate key='plugins.importexport.datacite.status.registered'}
											{elseif $item['status'] === 'markedRegistered'}
												{translate key='plugins.importexport.datacite.status.markedregistered'}
											{/if}
										</span>
									</span>
									</td>
								</tr>
								<tr id="datacitelistgrid-row-{$item['id']}-control-row" class="row_controls">
									<td colspan="6">
										<table id="chapters-table-{$item['id']}" class="datacite-table">
											<colgroup>
												<col class="grid-column column-select" style="width: 5%;">
												<col class="grid-column column-id" style="width: 7.5%;">
												<col class="grid-column column-title" style="width: 40%;">
												<col class="grid-column column-issue" style="width: 10%;">
												<col class="grid-column column-pubId" style="width: 20%;">
												<col class="grid-column column-status" style="width: 17.5%;">
											</colgroup>
											<tbody>
											{foreach $item['chapters'] as $chapterKey => $chapter}
												<tr id="chapter-row-{$item['id']}-c{$chapter['chapterId']}"
													class="gridRow">
													<td class="first_column">
														<label for="select-{$item['id']}-c{$chapter['chapterId']}"></label>
														<input type="checkbox" id="select-{$item['id']}-c{$chapter['chapterId']}" name="selectedChapters[]" style="height: 15px; width: 15px;" value="{$item['id']}-{$chapter['chapterId']}"
																{if empty($chapter['chapterDoi'])
																|| $chapter['chapterStatus'] === 'markedRegistered'}
																	disabled
																{else}
																	class="select-chapter-{$item['id']}"
																{/if}
														>
													</td>
													<td class=" pkp_helpers_text_left">
													<span id="cell-{$item['id']}-c{$chapter['chapterId']}-id" class="gridCellContainer">
														<span class="label before_actions">
															c{$chapter['chapterId']}
														</span>
													</span>
													</td>
													<td class=" pkp_helpers_text_left">
														<div id="cell-{$item['id']}-c{$chapter['chapterId']}-authors" class="gridCellContainer datacite-ellipsis datacite-authors">
															{$chapter['chapterAuthors']}
														</div>
														<div id="cell-{$item['id']}-c{$chapter['chapterId']}-title" class="gridCellContainer datacite-ellipsis">
															{$chapter['chapterTitle']}
														</div>
													</td>
													<td class=" pkp_helpers_text_left">
													<span id="cell-{$item['id']}-c{$chapter['chapterId']}-published" class="gridCellContainer">
														{$chapter['chapterPubDate']}
													</span>
													</td>
													<td class=" pkp_helpers_text_left">
													<span id="cell-{$item['id']}-c{$chapter['chapterId']}-pubId" class="gridCellContainer">
														<span class="label datacite-break-word">
															{if empty($chapter['chapterPubId'])}
																{$chapter['chapterDoi']}
															{else}
																{$chapter['chapterPubId']}
															{/if}
														</span>
													</span>
													</td>
													<td class=" pkp_helpers_text_left">
													<span id="cell-{$item['id']}-c{$chapter['chapterId']}-status" class="gridCellContainer">
														<span class="label datacite-break-word">
															{if $chapter['chapterStatus'] === 'notDeposited'}
																{translate key='plugins.importexport.datacite.status.todeposit'}
															{elseif $chapter['chapterStatus'] === 'registered'}
																{translate key='plugins.importexport.datacite.status.registered'}
															{elseif $chapter['chapterStatus'] === 'markedRegistered'}
																{translate key='plugins.importexport.datacite.status.markedregistered'}
															{/if}
														</span>
													</span>
													</td>
												</tr>
											{/foreach}
											</tbody>
										</table>
									</td>
								</tr>
							{/foreach}
							</tbody>
						</table>
						<div class="gridPaging">
							<div class="gridItemsPerPage">
								<label>{translate key='common.itemsPerPage'}:
									<select id="selItemsPerPage" class="itemsPerPage">
										<option value="10">10</option>
										<option value="25">25</option>
										<option value="50">50</option>
										<option value="75">75</option>
										<option value="100">100</option>
									</select>
								</label>
							</div>
							<div class="gridPages">
								<span id="datacite-page-start">{$startItem}</span> -
								<span id="datacite-page-end">{$endItem}</span> {translate key='plugins.importexport.datacite.table.of'}
								<span id="datacite-page-count-items">{$itemsSizeQueue}</span> {translate key='plugins.importexport.datacite.table.items'}
								<input type="button" id="firstPageId" name="firstPageId" class="datacite-nav-button"
									   value="<<">
								<input type="button" id="prevPageId" name="prevPageId" class="datacite-nav-button"
									   value="<">
								<input type="button" id="pageId-2" name="pageId-2" class="datacite-nav-button"
									   value="{$currentPage - 2}">
								<input type="button" id="pageId-1" name="pageId-1" class="datacite-nav-button"
									   value="{$currentPage - 1}">
								<input type="button" id="pageId" name="pageId" class="datacite-nav-button"
									   value="{$currentPage}">
								<input type="button" id="pageIdPlus1" name="pageIdPlus1" class="datacite-nav-button"
									   value="{$currentPage + 1}">
								<input type="button" id="pageIdPlus2" name="pageIdPlus2" class="datacite-nav-button"
									   value="{$currentPage +2}">
								<input type="button" id="nextPageId" name="nextPageId" class="datacite-nav-button"
									   value=">">
								<input type="button" id="lastPageId" name="lastPageId" class="datacite-nav-button"
									   value=">>">
							</div>
						</div>
					{/fbvFormSection}
				</div>
				<br/>
				{fbvFormSection class="formButtons form_buttons"}
					<button type="submit" id="deposit" name="deposit" value="1" class="deposit pkp_button_primary">
						{translate key='plugins.importexport.datacite.button.register'}
					</button>
					<button type="submit" id="markRegistered" name="markRegistered" value="1" class="markRegistered pkp_button">
						{translate key='plugins.importexport.datacite.button.markRegistered'}
					</button>
				{/fbvFormSection}
			</form>
		</div>
	</tab>
</tabs>
{/block}
