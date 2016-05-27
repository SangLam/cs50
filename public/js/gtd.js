$(updateTask);

function updateTask() {

	
	$.getJSON("test.php")
	.done(function(data) {
		
		var contentString;
		
		contentString = '<ul>';
		for (i = 0; i < data.length; i++) {
			contentString += '<li><div class="task">'+data[i].title+'</div></li>';
		}
		contentString +='</ul>';
		$('#task_stack').html(contentString);
	})
	.fail(function(jqXHR, textStatus, errorThrown) {
		console.log(errorThrown.toString());
	});
}