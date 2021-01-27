let datacitePaginationButtons, dataciteSelectItemsPerPage, dataciteMainForm, dataciteGrid, dataciteItemsCount;

function changeDatacitePagination(page = 1, itemsPerPage = 10) {
	let firstItem = (page * itemsPerPage) - itemsPerPage;
	let lastItem = firstItem + itemsPerPage - 1;
	let startPageText = 0;
	let endPageText;
	let endPage = $('#datacite-page-end');

	if( dataciteItemsCount > 0 )
	{
		startPageText = firstItem + 1;
	}

	for (let i = 0; i < dataciteItemsCount; i++) {
		let row = $('#datacitelistgrid-row-' + i)
		if (i < firstItem || i > lastItem) {
			row.removeClass('datacite-show-row');
			row.addClass('datacite-hide');
		} else {
			row.removeClass('datacite-hide');
			row.addClass('datacite-show-row');
		}
	}

	startPageText = startPageText.toString();
	$('#datacite-page-start').text(startPageText);
	if (lastItem > dataciteItemsCount) {
		endPage.text( String(dataciteItemsCount) );
	} else {
		endPageText = (lastItem + 1).toString();
		endPage.text(endPageText);
	}
}

function changeDatacitePaginationButton(currentPage = 1) {
	let itemsPerPage = parseInt(dataciteSelectItemsPerPage.val());
	let totalPages = 1;
	let pageMinus2 = $('#datacite-pageId-2');
	let pageMinus1 = $('#datacite-pageId-1');
	let pagePlus1 = $('#datacite-pageIdPlus1');
	let pagePlus2 = $('#datacite-pageIdPlus2');

	if(  dataciteItemsCount > 0 )
	{
		totalPages = Math.ceil(dataciteItemsCount / itemsPerPage);
	}

	$('#datacite-pageId').val(currentPage.toString());
	pageMinus2.val((currentPage - 2).toString());
	pageMinus1.val((currentPage - 1).toString());
	pagePlus1.val((currentPage + 1).toString());
	pagePlus2.val((currentPage + 2).toString());
	datacitePaginationButtons.removeClass('datacite-hide');

	if (currentPage === 1) {
		$('#datacite-firstPageId').addClass('datacite-hide');
		$('#datacite-prevPageId').addClass('datacite-hide');
		pageMinus2.addClass('datacite-hide');
		pageMinus1.addClass('datacite-hide');
	}

	if (currentPage === totalPages) {
		$('#datacite-lastPageId').addClass('datacite-hide');
		$('#datacite-nextPageId').addClass('datacite-hide');
		pagePlus2.addClass('datacite-hide');
		pagePlus1.addClass('datacite-hide');
	}

	if (currentPage === totalPages - 1) {
		pagePlus2.addClass('datacite-hide');
	}

	if (currentPage === 2) {
		pageMinus2.addClass('datacite-hide');
	}
}

function bindDataciteCollapseButtonClick() {
	$('td.first_column > a').click( function () {
		let classList = $(this).attr('class').split(/\s+/);
		let itemId = '';
		$.each(  classList, function (index, item) {
			if( item.toLowerCase().indexOf( 'dropdown-' ) >= 0 ) {
				itemId = item.replace( 'dropdown-', '' );
				return false;
			}
		});

		if( itemId !== '' ) {
			let collapseTable = $('#datacitelistgrid-row-' + itemId + '-control-row');
			if ($(this).hasClass( 'show_extras' )) {
				$(this).addClass( 'hide_extras' );
				$(this).removeClass( 'show_extras' )
				collapseTable.css('display', 'table-row');
			}
			else {
				$(this).addClass( 'show_extras' );
				$(this).removeClass( 'hide_extras' )
				collapseTable.css('display', 'none');
			}
		}
	});
}

function closeDataciteAllChapterTables() {
	$('a.hide_extras').each(function () {
		$(this).click();
	});
}

$(document).ready(function () {
	datacitePaginationButtons = $('.datacite-nav-button');
	dataciteSelectItemsPerPage = $('#datacite-selItemsPerPage');
	dataciteMainForm = $('#datacite-queueXmlForm');
	dataciteGrid = $('#datacitelistgrid');
	dataciteItemsCount = $('#datacitelistgrid-table > tbody > tr').length / 2;

	bindDataciteCollapseButtonClick();
	dataciteGrid.pkpHandler(
		'$.pkp.controllers.grid.GridHandler',
		{
			bodySelector: '#datacitelistgrid-table',
		});

	$('body').on('change','.submissionCheckbox',function(){
		let itemId = $(this).val();
		let collapseTable = $('#datacitelistgrid-row-' + itemId + '-control-row')
		if (this.checked) {
			$('input.select-chapter-' + itemId).each(function () {
				$(this).prop('checked', true);
			});
		} else {
			$('input.select-chapter-' + itemId).each(function () {
				$(this).prop('checked', false);
			});
		}
		if (collapseTable.is(':hidden')) {
			$('.dropdown-' + itemId).trigger('click');
		}
	});

	$('#datacite-search-button').click(function () {
		$('body').addClass('waiting');
		$.ajax({
			type: 'POST',
			dataType: "html",
			data: {
				'csrfToken':$('input[name ="csrfToken"]').val(),
				'isAjax':true,
				'sel-search-type':$('#datacite-sel-search-type').val(),
				'search-text':$('#datacite-search-text').val(),
				'sel-search-status':$('#datacite-sel-search-status').val()
			},
			success: function (data) {
				$('#datacitelistgrid-table > tbody').html(data);
				if( data !== '' ) {
					dataciteItemsCount = $('#datacitelistgrid-table > tbody > tr').length / 2;
				}
				else
				{
					dataciteItemsCount = 0;
				}
				$('#datacite-page-count-items').html( dataciteItemsCount );
				closeDataciteAllChapterTables();
				changeDatacitePaginationButton();
				changeDatacitePagination();
				bindDataciteCollapseButtonClick();
				$('body').removeClass('waiting');
			},
			error: function(jqXHR, textStatus, errorThrown) {
				$('body').removeClass('waiting');
				alert( 'Error: Status: ' + jqXHR.status + ': ' + errorThrown);
			}
		});
	});

	datacitePaginationButtons.click(function () {
		let buttonValue = $(this).val();
		let itemsPerPage = parseInt(dataciteSelectItemsPerPage.val());
		let currentPage = parseInt($('#datacite-pageId').val());
		let totalPages = Math.ceil(dataciteItemsCount / itemsPerPage);

		if (buttonValue === '<<') {
			buttonValue = 1;
		} else if (buttonValue === '<') {
			if (currentPage !== 1) {
				buttonValue = currentPage - 1;
			} else {
				buttonValue = 1;
			}

		} else if (buttonValue === '>') {
			if (currentPage !== totalPages) {
				buttonValue = currentPage + 1;
			} else {
				buttonValue = totalPages;
			}
		} else if (buttonValue === '>>') {
			buttonValue = totalPages
		}

		closeDataciteAllChapterTables();
		changeDatacitePagination(parseInt(buttonValue), itemsPerPage);
		changeDatacitePaginationButton(parseInt(buttonValue));
	});

	dataciteSelectItemsPerPage.change(function () {

		closeDataciteAllChapterTables();
		let itemsPerPage = $('#datacite-selItemsPerPage').val();
		changeDatacitePaginationButton();
		let currentPage = $('#datacite-pageId').val();
		changeDatacitePagination(parseInt(currentPage), parseInt(itemsPerPage));

	});

	$('#datacite-search-text').keydown(function(event) {
		// noinspection JSUnresolvedVariable
		if (event.keyCode === 13) {
			event.preventDefault();
			$('#datacite-search-button').click();
		}
	});

	changeDatacitePaginationButton();
	changeDatacitePagination();
});
