var actioned_id = '';
$('.action').click(function(){
	actioned_id = $(this).data('id');
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
		$('#box-'+$actioned_id).remove();
		actioned_id = '';
	}).error(function(data){
		console.log(data);
	});
});