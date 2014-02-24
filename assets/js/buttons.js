$(document).ready(function(){
	var id = window.location.pathname.split('/')[2];
	$('#buttons #edit').attr('href', '/edit/' + id);

	$('#buttons #delete').click(function(){
		if(confirm('You sure you want to delete this post?')){
			$.ajax('/post', {
				type: 'post',
				dataType: 'json',
				data: {
					'_METHOD': 'DELETE',
					'id': id
				},
				success: function(response){
					if(response){
						window.location = '/';
						// add flash
					}
				},
				error: function(xhr, status, error){
					console.log('error:', xhr.responseText);
					// display it
				}
			});
		}
	});

	$('#controls #delete').click(function(){
		if(confirm('You sure you want to delete this comment?')){
			$.ajax('/comment', {
				type: 'post',
				dataType: 'json',
				data: {
					'_METHOD': 'DELETE',
					'id': $(this).data('id')
				},
				success: function(response){
					if(response){
						window.location = '/post/' + id;
						// add flash
					}
				},
				error: function(xhr, status, error){
					console.log('error:', xhr.responseText);
					// display it
				}
			});
		}
	});

	$('#controls #ban').click(function(){
		if(confirm('You sure you want to ban this user?')){
			$.ajax('/ban', {
				type: 'post',
				dataType: 'json',
				data: {
					'id': $(this).data('id')
				},
				success: function(response){
					if(response){
						window.location = '/post/' + id;
						// add flash
					}
				},
				error: function(xhr, status, error){
					console.log('error:', xhr.responseText);
					// display it
				}
			});
		}
	});
});
