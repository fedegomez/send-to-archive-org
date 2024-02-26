import Swal from 'https://cdn.jsdelivr.net/npm/sweetalert2@11.10.3/+esm';

(function( $ ) {
	'use strict';

	const toast = Swal.mixin({
		toast: true,
		animation: false,
		showConfirmButton: false,
		timer: 3000,
		timerProgressBar: true,
	});

	function sendAjaxRequestToArchiveOrg(postId) {
		return new Promise((resolve, reject) => {
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'send_to_archive_org',
					post_id: postId,
					nonce: send_to_archive_org.nonce
				},
				success: function(response) {
					resolve({'data': response, 'post_id': postId});
				},
				error: function(xhr, status, error) {
					reject(error);
				}
			});
		});
	}

	function getSnapShots() {
		let posts = $('#the-list tr').map(function() {
			let id = $(this).attr('id');
			let match = id.match(/^post-(\d+)$/);
			if (match) {
				return match[1];
			}
		}).get();

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'get_snapshots',
				posts: posts,
				nonce: send_to_archive_org.nonce
			},
			success: function(response) {
				if (response.success) {
					let data = response.data;
					for (let post in data) {
						$("#the-list tr#post-" + post + " td.archive_org").html(data[post]);
					}
				}
			},
			error: function(xhr, status, error) {
				toast.fire({
					html: error,
					icon: 'error'
				});
			}
		});
	}

	$(function() {

		let pageParam = new URLSearchParams(window.location.search).get("page");

		if (pageParam === "send_to_archive_org") {
			function toggleFields() {
				$('#send_results_to').closest('tr').toggle($('#report_result').val() === 'yes');
			}

			$('#report_result').change(toggleFields);
			toggleFields();

		} else {

			getSnapShots();

			$('.send_to_archive_org').click(function(e) {
				e.preventDefault();
				let postId = $(this).data('post_id');

				sendAjaxRequestToArchiveOrg(postId).then(
					(response) => {
						if (response.data.success) {
							toast.fire({
								html: send_to_archive_org.success_message,
								icon: 'success'
							});
							$("#the-list tr#post-" + postId + " td.archive_org").html(response.data.data);
						} else {
							toast.fire({
								html: response.data.data,
								icon: 'error'
							});
						}
					},
					(error) => {
						toast.fire({
							html: error,
							icon: 'error'
						});
					}
				);

			});

			$(document).ajaxComplete(function (event, xhr, settings) {
				if (settings.data && settings.data.indexOf('action=inline-save') !== -1) {
					let postId = settings.data.match(/post_ID=(\d+)/)[1];
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'get_snapshots',
							post: postId,
							nonce: send_to_archive_org.nonce
						},
						success: function(response) {
							if (response.success) {
								let data = response.data;

								let columnIndex = -1;
								$('table.wp-list-table thead th').each(function(i) {
									if ($(this).text().trim() === 'Archive.org') {
										columnIndex = i;
										return false;
									}
								});

								if (columnIndex !== -1) {
									for (let post in data) {
										$("#the-list tr#post-" + postId).find('td:eq(' + (columnIndex-1) + ')').after('<td class="archive_org column-archive_org" data-col-name="Archive.org"></td>');
										$("#the-list tr#post-" + postId + " td.archive_org").html(data[post]);
									}
								}
							}
						},
						error: function(xhr, status, error) {
							console.log(error);
						}
					});
				}
			});
		}
	});

})( jQuery );
