<?php
include_once 'functions/comun/cabses.php';
$numeroInforme = '22';
include_once 'functions/comun/autorizacion.php';
?>
<!DOCTYPE html>
<html lang="es">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1">
	    <meta name="robots" content="noindex,nofollow">
		<title>Diario facturas clientes</title>
		<link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	    <link href="assets/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
	    <script src="assets/jquery.min.js"></script>
	    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
		<link href="assets/font-awesome/css/font-awesome.min.css" rel="stylesheet" />
		<style>
			#tabla thead th {
				vertical-align: middle;
			}
		</style>
	</head>
	<?php 
		include_once 'template/menu.php';
	?>
	<body class="gris">
		<div id="wrap">
			<div class="row" style="margin: 80px 50px 10px 50px;">
				<table style="width: 100%; margin-bottom:20px;">
			        <tr>
			        	<td style="padding: 5px;">
			                <div class="form-inline">
			                	Fecha factura
			                    <div class="input-group">
			                        <div class="input-group-addon">Desde</div>
			                        <input type="text" class="input-date input-sm form-control" id="selFechaInicio" name="selFechaInicio" style="width:100px" value="" autocomplete="off"/>
			                        <div class="input-group-addon">Hasta</div>
			                        <input type="text" class="input-date input-sm form-control" id="selFechaFin" name="selFechaFin" style="width:100px" value="<?php echo date('m-Y'); ?>" autocomplete="off"/>
			                    </div>
			                </div>
			            </td>
			        </tr>
			        <tr>
			            <td style="padding: 5px;">
			                <select id="selProyectos" class="form-control selectpicker" data-live-search="true" style="width:400px">
			                    
			                </select>
			            </td>
			            <td style="padding: 5px;">
			                <select id="selClientes" class="form-control selectpicker" data-live-search="true" style="width:400px">
			                    
			                </select>
			            </td>
			            <td>
			                <button style="margin-left: 20px;" type="submit" class="btn btn-primary btn-sm" onclick="pintarTabla();">Filtrar</button>
			            </td>
			            <td>
			                <a href="javascript:window.location.reload()" style="margin-left: 20px;" type="submit" class="btn btn-default btn-sm">Borrar</a>
			            </td>
			            <td>
			                <form action="functions/interfaces/interface_diarioFacturasClientes.php" method="post" >
			            	<!-- este formulario imita el ajax de pintarTabla, por eso tiene los mismos "name" que los valores que se mandan en ese ajax, no tiene nada que ver con los name originales del form principal, y el id es solo para meterle los mismos valores que se eligen -->
			            		<input type="hidden" name="accion" value="imprimirExcel" />
			            		<input type="hidden" id="selFechaInicio2" name="fechaInicio" value="" />
			            		<input type="hidden" id="selFechaFin2" name="fechaFin" value="" />
			            		<input type="hidden" id="selProyectos2" name="proyecto" value="" />
			            		<input type="hidden" id="selClientes2" name="cliente" value="" />
			               		<button id="btnExcel" style="margin-left: 20px" class="btn btn-default btn-sm">Excel</button>
			               	</form>
			            </td>
			        </tr>
			    </table>
	            <div id="tablaContenido" class="col-md-12">
	            	<!-- se debe crear la tabla entera pq cambian las cabeceras según si tiene marcado Mostrar detalle, entonces el sticky header se carga la tabla -->
	            </div>
	        </div>
        </div>
		<script src="assets/jquery-ui.min.js"></script>
		<link href="assets/jquery-ui.min.css" rel="stylesheet" />
		<link href="assets/jquery-ui.theme.min.css" rel="stylesheet" />
    	<link href="assets/datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
		<script src="assets/datepicker/js/bootstrap-datepicker.min.js"></script>
        <script src="assets/datepicker/locales/bootstrap-datepicker.es.min.js"></script>
		<script src="assets/datatables/jquery.dataTables.js"></script>
		<script src="assets/datatables/dataTables.bootstrap.js"></script>
		<link href="assets/chosen/chosen.css" rel="stylesheet" />
		<script src="assets/chosen/chosen.jquery.min.js"></script>
		<script>
			var loading = "<img style='width:50px;position:absolute;top:150%;left:50%;margin-left:-25px;' src='img/loading.gif' />";
			var loading2 = "<img style='width:38px;margin-left:40px;' src='img/loading.gif' />";

			$(document).ready(function() {
				llenarSelectProyectos();
				llenarSelectClientes();

				$(".input-date").datepicker({ 
					clearBtn: true, 
					language: "es", 
					autoclose: true,
					format: "mm-yyyy", 
					viewMode: "month",
					minViewMode: "months",
					orientation: "right top"  
				});
			});

			function llenarSelectProyectos() {
				$.ajax({
                    type: "POST",
                    url:  "functions/interfaces/interface_diarioFacturasClientes.php",
                    data: {accion: "proyectos"},
                    success: function(data) {
                        var respuesta = $.parseJSON(data);
                    	$("#selProyectos").html(respuesta.html);
                    	$("#selProyectos").chosen({ search_contains: true });
                    }
                });
			}

			function llenarSelectClientes() {
				$.ajax({
                    type: "POST",
                    url:  "functions/interfaces/interface_diarioFacturasClientes.php",
                    data: {accion: "clientes"},
                    success: function(data) {
                        var respuesta = $.parseJSON(data);
                    	$("#selClientes").html(respuesta.html);
                    	$("#selClientes").chosen({ search_contains: true });
                    }
                });
			}

			function pintarTabla() {
				var fechaInicio = $("#selFechaInicio").val();
				var fechaFin = $("#selFechaFin").val();
				var proyecto = $("#selProyectos").val();
				var cliente = $("#selClientes").val();

				/* para imprimir excel, es otro form, y se necesitan estos valores como campos ocultos que enviar */
				$("#selFechaInicio2").val(fechaInicio);
				$("#selFechaFin2").val(fechaFin);
				$("#selProyectos2").val(proyecto);
				$("#selClientes2").val(cliente);

				$.ajax({
	                url: "functions/interfaces/interface_diarioFacturasClientes.php",
	                data: {accion: "pintarTabla", fechaInicio: fechaInicio, fechaFin: fechaFin, proyecto: proyecto, cliente: cliente},
	                method: "post",
	                beforeSend: function() {
	                	$("#tablaContenido").html(loading);
	                },
	                success: function(data) {
                		var respuesta = $.parseJSON(data);
                		$("#tablaContenido").html(respuesta.html);
		            	// sticky header
		            	$("#tabla").floatThead({
				            scrollingTop: 50
				        });
	                }
	            });
			}
		</script>
		<?php
			include_once 'template/footer-comun.php';
		?>
		<script src="assets/sticky-header.js"></script>
	</body>
</html>