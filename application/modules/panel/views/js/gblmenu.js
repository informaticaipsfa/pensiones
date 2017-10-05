/**
* Cargar menus del sistema
*
*

*/


_INTERFAZ = '';
_PRV = {};
_ACT = false;

 $(function () {
 	s = window.location.pathname;
 	a = s.split("/");
	_INTERFAZ = a[a.length-1];
	
 	init();
 	
 });

function init(){
	var url = sUrlP + 'obtenerMenu';
	menu = '';	
	var clase = '';
	$.post(url).done(function (data){
		$("#navegacion-menu").html('<li class="header">NAVEGACIÓN PRINCIPAL</li>');
		$.each(data, function (x,y){
			menu = '<li class="treeview"><a href="#">';	                
	        textomenu = '<span>' + x + '</span><i class="fa fa-angle-left pull-right"></i></a><ul class="treeview-menu">';	              
			var submenu = '';
			$.each(y, function(cla, val){
				submenu = '';			
				clase = '<i class="' + val.clase + '"></i>';	           	
				if (val.priv_!=null && val.url == _INTERFAZ) {
					_PRV = val.priv_;					
					cBtn(_PRV);
				}
				
	            $.each(y, function(c, v){ 	            
					submenu += '<li><a href="' + sUrlP + v.url + '"><i class="' + v.clase_ + '"></i> ' + v.desc + '</a></li>';				
				}); 
	           
			});
			
			 menu += clase + textomenu + submenu +'</ul></li>';
			 $("#navegacion-menu").append(menu);
		});

		
			
	});

}


function cBtn(_DATA){

	$.each(_DATA, function(p, q){
		
		if (q.tipo == "btn" && q.visi == 1){
			$("#divBotones").append('<button type="button" class="btn btn-success"\
                onclick="' + q.func + '()" id=\'' + q.cod + '\'><i class="' + q.clas + '">\
                </i>&nbsp;&nbsp;' + q.nomb + '</button>');
         }
         if(q.tipo == "tbl_"){
         	_ACT = true;
         }
	})
}


function vBtn(){
	
	$.each(_PRV, function(p, q){		
		
		if (q.tipo == "tbl_" && q.visi == "0"){
			$("#" + q.cod).hide();
		}
		if (q.tipo == "div" && q.visi == "1"){
			$("#" + q.cod).show();
		}
	});
}

function show(_ID){
	$("#myModal").modal('show');
}




