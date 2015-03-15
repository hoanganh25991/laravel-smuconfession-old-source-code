$('.action').click(function(){
	console.log($(this).data('id'));
	console.log($(this).data('action'));
	$.ajax({
		type: 'POST',
		url: '/smusg/0/action',
		data: {
			id: $(this).data('id'),
			action: $(this).data('action'),
			confession: $('#content-'+$(this).data('id')).html()
		},
		dataType: 'json',
	}).done(function(data){
		console.log(data);
	}).error(function(data){
		console.log(data);
	});
});