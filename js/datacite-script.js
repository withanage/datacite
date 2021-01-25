let paginationButtonsDP, selectItemsPerPageDP, mainFormDP, gridDP, itemsCountDP;

function changeDatacitePagination(page = 1, itemsPerPage = 10) {
	let firstItem = (page * itemsPerPage) - itemsPerPage;
	let lastItem = firstItem + itemsPerPage - 1;
	let startPageText = 0;
	let endPageText;
	let endPage = $('#datacite-page-end');

	if( itemsCountDP > 0 )
	{
		startPageText = firstItem + 1;
	}

	for (let i = 0; i < itemsCountDP; i++) {
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
	if (lastItem > itemsCountDP) {
		endPage.text( String(itemsCountDP) );
	} else {
		endPageText = (lastItem + 1).toString();
		endPage.text(endPageText);
	}
}

function changeDatacitePaginationButton(currentPage = 1) {
	let itemsPerPage = parseInt(selectItemsPerPageDP.val());
	let totalPages = 1;
	let pageMinus2 = $('#pageId-2');
	let pageMinus1 = $('#pageId-1');
	let pagePlus1 = $('#pageIdPlus1');
	let pagePlus2 = $('#pageIdPlus2');

	if(  itemsCountDP > 0 )
	{
		totalPages = Math.ceil(itemsCountDP / itemsPerPage);
	}

	$('#pageId').val(currentPage.toString());
	pageMinus2.val((currentPage - 2).toString());
	pageMinus1.val((currentPage - 1).toString());
	pagePlus1.val((currentPage + 1).toString());
	pagePlus2.val((currentPage + 2).toString());
	paginationButtonsDP.removeClass('datacite-hide');

	if (currentPage === 1) {
		$('#firstPageId').addClass('datacite-hide');
		$('#prevPageId').addClass('datacite-hide');
		pageMinus2.addClass('datacite-hide');
		pageMinus1.addClass('datacite-hide');
	}

	if (currentPage === totalPages) {
		$('#lastPageId').addClass('datacite-hide');
		$('#nextPageId').addClass('datacite-hide');
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

function bindCollapseButtonClick() {
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

function closeAllChapterTables() {
	$('a.hide_extras').each(function () {
		$(this).click();
	});
}

$(document).ready(function () {
	paginationButtonsDP = $('.datacite-nav-button');
	selectItemsPerPageDP = $('#selItemsPerPage');
	mainFormDP = $('#queueXmlForm');
	gridDP = $('#datacitelistgrid');
	itemsCountDP = $('#datacitelistgrid-table > tbody > tr').length / 2;

	bindCollapseButtonClick();
	gridDP.pkpHandler(
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
				'sel-search-type':$('#sel-search-type').val(),
				'search-text':$('#search-text').val(),
				'sel-search-status':$('#sel-search-status').val()
			},
			success: function (data) {
				$('#datacitelistgrid-table > tbody').html(data);
				if( data !== '' ) {
					itemsCountDP = $('#datacitelistgrid-table > tbody > tr').length / 2;
				}
				else
				{
					itemsCountDP = 0;
				}
				$('#datacite-page-count-items').html( itemsCountDP );
				closeAllChapterTables();
				changeDatacitePaginationButton();
				changeDatacitePagination();
				bindCollapseButtonClick();
				$('body').removeClass('waiting');
			},
			error: function(jqXHR, textStatus, errorThrown) {
				$('body').removeClass('waiting');
				alert( 'Error: Status: ' + jqXHR.status + ': ' + errorThrown);
			}
		});
	});

	paginationButtonsDP.click(function () {
		let buttonValue = $(this).val();
		let itemsPerPage = parseInt(selectItemsPerPageDP.val());
		let currentPage = parseInt($('#pageId').val());
		let totalPages = Math.ceil(itemsCountDP / itemsPerPage);

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

		closeAllChapterTables();
		changeDatacitePagination(parseInt(buttonValue), itemsPerPage);
		changeDatacitePaginationButton(parseInt(buttonValue));
	});

	selectItemsPerPageDP.change(function () {

		closeAllChapterTables();
		let itemsPerPage = $('#selItemsPerPage').val();
		changeDatacitePaginationButton();
		let currentPage = $('#pageId').val();
		changeDatacitePagination(parseInt(currentPage), parseInt(itemsPerPage));

	});

	$('#search-text').keydown(function(event) {
		// noinspection JSUnresolvedVariable
		if (event.keyCode === 13) {
			event.preventDefault();
			$('#datacite-search-button').click();
		}
	});

	changeDatacitePaginationButton();
	changeDatacitePagination();
});
