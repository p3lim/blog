$(document).ready(function(){
	var article = $('article');

	ace.config.set('basePath', '/assets/js/libs/ace');
	editor = ace.edit('editor');
	editor.setTheme('ace/theme/tomorrow_night_eighties');
	editor.setShowPrintMargin(false);
	editor.getSession().setUseWorker(false);
	editor.getSession().setMode('ace/mode/html');
	editor.getSession().setUseWrapMode(true);
	editor.setValue(article.html());
	editor.clearSelection();
	editor.getSession().on('change', function(event){
		article.html(editor.getValue());
	});

	$('.publish').click(function(){
		var title = $('.title').val();
		if(title.length === 0){
			return alert('Missing title');
		}

		var content = editor.getValue();
		if(content.length > 0){
			if(confirm('Are you sure you want to publish this?')){
				var id = $(this).attr('data-id');
				if(id){
					$.ajax('/post', {
						type: 'post',
						dataType: 'json',
						data: {
							'_METHOD': 'PUT',
							'id': parseInt(id),
							'title': title,
							'content': content
						},
						success: function(response){
							window.location = '/post/' + response.id;
						},
						error: function(xhr){
							alert('ERROR: ' + xhr.responseText);
						}
					});
				} else {
					$.ajax('/post', {
						type: 'post',
						dataType: 'json',
						data: {
							'title': title,
							'content': content
						},
						success: function(response){
							window.location = '/post/' + response.id;
						},
						error: function(xhr){
							alert('ERROR: ' + xhr.responseText);
						}
					});
				}
			}
		} else {
			return alert('No content');
		}
	});

	$('.discard').click(function(){
		var content = editor.getValue();
		if(content.length > 0){
			if(!confirm('Are you sure you want to discard this? ALL CHANGES WILL BE LOST!')){
				return;
			}
		}

		var id = $('.publish').attr('data-id');
		if(id){
			window.location = '/post/' + id;
		} else {
			window.location = '/';
		}
	});
});
