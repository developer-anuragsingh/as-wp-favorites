jQuery( document ).ready( function ( e ) {
	alert("hi");
	jQuery("#form-favorite").submit(function(){
		alert( "Handler for .submit() called." );
	});
});