<?php
// ini_set('display_errors', 'on');
class contratos extends bd {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerDatosEmpresa($cif) {
    	$aResultados = array();
    	$select = 'TOP 1 Domicilio,CodigoPostal,Municipio,RazonSocial,Telefono,Provincia';
    	$from = DB_SAGE.'Proveedores';
    	$where = "WHERE CifDni='".$cif."'";
    	$aInfo = $this->consulta($select, $from, $where);
		if (is_array($aInfo)) {
			$razonSocial = $aInfo[0]['RazonSocial'];
			$tlf = $aInfo[0]['Telefono'];
			$domicilio = '';
			$cp = '';
			$municipio = '';
			$provincia = '';

			if (!empty($aInfo[0]['Domicilio'])) {
				$domicilio = $aInfo[0]['Domicilio'];
			}
			if (!empty($aInfo[0]['CodigoPostal'])) {
				$cp = $aInfo[0]['CodigoPostal'];
			}
			if (!empty($aInfo[0]['Municipio'])) {
				$municipio = $aInfo[0]['Municipio'];
			}
			if (!empty($aInfo[0]['Provincia'])) {
				$provincia = $aInfo[0]['Provincia'];
			}

			$aResultados = array('empresa' 	=> $razonSocial, 
								'domicilio' => $domicilio, 
								'cp' 		=> $cp, 
								'municipio' => $municipio, 
								'provincia' => $provincia, 
								'telefono' 	=> $tlf);
		}
		return json_encode($aResultados);
    }

    public function obtenerUltimaFactura($codigoProveedor, $obra) { /* obtener datos de última factura - pintar en caso de liquidacion */
    	$aResultados = array();

    	/* siempre que tenga codigo de proveedor (empresa) y codigo de proyecto (obra) */
    	if ($codigoProveedor != '' && $obra != '') {
    		$select = 'TOP 1 sufacturano as NumeroFactura, fechaemision, ImporteLiquido';
	    	$from = DB_APP.'facturacionproveedores';
	    	$where = "WHERE codigoproveedor='".$codigoProveedor."' AND codigoproyecto = '".$obra."'";
	    	$order = 'ORDER BY fechaemision DESC';
	    	$aInfo = $this->consulta($select, $from, $where, $order);

			if (is_array($aInfo)) {
				$aplicarRetencion = 0;
				$numFactura = $aInfo[0]['NumeroFactura'];
				$fechaFactura = $aInfo[0]['fechaemision']->format('d/m/Y');
				$importe = $this->formatoNumero($aInfo[0]['ImporteLiquido'], true, false, 2, false, false);				

				/* total retencion de todas las facturas, no solo de la ultima */
				$select = 'SUM(Retencion) as TotalRetencion';
	    		$aTotalRetencion = $this->consulta($select, $from, $where);
				if (is_array($aTotalRetencion)) {
					$importeRetencion = $this->formatoNumero($aTotalRetencion[0]['TotalRetencion'], true, false, 2, false, false);

					if ($importeRetencion > 0) {
						$aplicarRetencion = 1;
					}
				}

				$aResultados = array('aplicarRetencion' => $aplicarRetencion, 
									'importeRetencion' 	=> $importeRetencion, 
									'numFactura' 		=> $numFactura, 
									'fechaFactura' 		=> $fechaFactura, 
									'importe' 			=> $importe);
			}
    	}
    	
		return json_encode($aResultados);
    }

    public function obtenerTodosProyectos() { // obras
    	$aResultados;
    	$select = "DISTINCT(CodigoProyecto), Proyecto+case when finalizado = -1 then '--(FINALIZADA)' else '--(ACTIVA)' end as Proyecto, Descripcion";
    	$from = DB_SAGE.'Proyectos';
    	$where = "WHERE Proyecto <> '' and codigoempresa = 1";
    	$order = 'ORDER BY Proyecto';
    	$aProyectos = $this->consulta($select, $from, $where, $order);
		if (is_array($aProyectos)) {
			$jsonObras = array();
			foreach ($aProyectos as $d) {
				$jsonObras[] = array(
		            'id' => $d['CodigoProyecto'],
		            'value' => ucfirst($d['Proyecto']),
		            'desc' => ucfirst($d['Descripcion'])
		        );
			}
			$aResultados[] = array('jsonObras' => $jsonObras);
			return json_encode($aResultados);
		} else {
			return 'false';
		}
    }

    public function obtenerTodasEmpresas() { // empresas o proveedores o mercantiles...
    	$aResultados;
    	$select = 'DISTINCT CifDni,Domicilio,CodigoPostal,Municipio,Provincia,RazonSocial,Telefono,CodigoEmpresa,CodigoProveedor';
    	$from = DB_SAGE.'Proveedores';
    	$where = "WHERE CodigoEmpresa=1";
    	$group = 'GROUP BY CifDni,Domicilio,CodigoPostal,Municipio,Provincia,RazonSocial,Telefono,CodigoEmpresa,CodigoProveedor';
    	$order = 'ORDER BY RazonSocial ASC';
    	$aEmpresas = $this->consulta($select, $from, $where, $group, $order);
		if (is_array($aEmpresas)) {
			$jsonEmpresas = array();
			foreach ($aEmpresas as $d) {
				$jsonEmpresas[] = array(
		            'id' => $d['CifDni'],
		            'value' => ucfirst($d['RazonSocial']),
		            'domicilio' => ucfirst($d['Domicilio']),
		            'cp' => $d['CodigoPostal'],
		            'municipio' => ucfirst($d['Municipio']),
		            'provincia' => ucfirst($d['Provincia']),
		            'telefono' => $d['Telefono'],
		            'codigoProveedor' => $d['CodigoProveedor']
		        );
			}
			$aResultados[] = array('jsonEmpresas' => $jsonEmpresas);
			return json_encode($aResultados);
		} else {
			return 'false';
		}
    }

    public function obtenerTodosAdmins($cifEmpresa) {
    	$where = '';
		if ($cifEmpresa != '') {
			$where = "WHERE CifEmpresa = '" . $cifEmpresa . "' ";
		}

    	$aResultados;
    	$select = 'CodigoAdministrador,Nombre,Dni,Cargo,CifEmpresa';
    	$from = DB_APP.DB_PREFIJO.'AdministradoresContrato';
    	$order = 'ORDER BY Nombre ASC';
    	$aAdmins = $this->consulta($select, $from, $where, $order);
		if (is_array($aAdmins)) {
			$jsonAdministradores = array();
			foreach ($aAdmins as $d) {
				$jsonAdministradores[] = array(
		            'id' => $d['CodigoAdministrador'],
		            'value' => ucfirst($d['Nombre']),
		            'dni' => $d['Dni'],
		            'cargo' => ucfirst($d['Cargo']),
		            'cifEmpresa' => $d['CifEmpresa']
		        );
			}
			$aResultados[] = array('jsonAdministradores' => $jsonAdministradores);
			return json_encode($aResultados);
		} else {
			return 'false';
		}
    }

    public function obtenerTodosUsuarios() {
    	/* este select lo voy a llenar solo con los que descargan contratos */
    	$aResultados;
    	$select = 'DISTINCT UsuarioDescarga';
    	$from = DB_APP.DB_PREFIJO.'DescargasContratos';
    	$order = 'ORDER BY UsuarioDescarga ASC';
    	$aUsuarios = $this->consulta($select, $from, '', $order);
		if (is_array($aUsuarios)) {
			$jsonUsuarios = array();
			foreach ($aUsuarios as $d) {
				$jsonUsuarios[] = array(
		            'id' => $d['UsuarioDescarga'],
		            'value' => ucfirst($d['UsuarioDescarga'])
		        );
			}
			$aResultados[] = array('jsonUsuarios' => $jsonUsuarios);
			return json_encode($aResultados);
		} else {
			return 'false';
		}
    }

    public function obtenerContratos($form, $excel = false) {
		$fechaInicio = $form['fechaInicio'];
		$fechaFin = $form['fechaFin'];
		$usuario = $form['usuario'];
		$mercantil = $form['mercantil'];
		$obra = $form['obra'];
		$contrato = $form['contrato'];
		$expediente = $form['expediente'];

		$where = '';
		$aWhere = array();
		$flag = false;

		if ($usuario != -1 && !empty($usuario)) {
			$flag = true;
			$aWhere['UsuarioDescarga'] = $usuario;
		}

		if ($mercantil != -1 && !empty($mercantil)) {
			$flag = true;
			$aWhere['CifDni'] = $mercantil;
		}

		if ($obra != -1 && !empty($obra)) {
			$flag = true;
			$aWhere['CodigoObra'] = $obra;
		}

		if ($contrato != -1 && !empty($contrato)) {
			$flag = true;
			$aWhere['CodigoTipoContrato'] = $contrato;
		}

		if (!empty($expediente)) {
			$flag = true;
			$aWhere['CodigoExpediente'] = $expediente;
		}

		if ($flag) {
			$where = ' WHERE ';
			$contador = 0;
			foreach ($aWhere as $key => $w) {
				if ($contador > 0) {
					$where .= ' AND ';
				}
				if ($key == 'CodigoExpediente') {
					$where .= $key . " like '%" . $w . "%' ";
				} else {
					$where .= $key . " = '" . $w . "' ";
				}
				$contador++;
			}
		}

		if (!empty($fechaInicio)) {
			if (!$flag) {
				$where .= 'WHERE ';
			} else {
				$where .= ' AND ';
			}
			$fechaI = str_replace("/", "-", $fechaInicio);
			$where .= " CAST(FechaRegistro as date) >= '" . $fechaI . "' "; 
		}

		if (!empty($fechaFin)) {
			if (!$flag && $where == '') {
				$where .= 'WHERE ';
			} else {
				$where .= ' AND ';
			}
			$fechaF = str_replace("/", "-", $fechaFin);
			$where .= " CAST(FechaRegistro as date) <= '" . $fechaF . "' "; 
		}
		// return $where;
		$aResultados;
    	$select = 'IdUnico,FechaRegistro,UsuarioDescarga,NombreMercantil,CodigoObra,NombreObra,TipoContrato,CodigoExpediente';
    	$from = DB_APP.DB_PREFIJO.'DescargasContratos';
    	$order = 'ORDER BY FechaRegistro DESC';
    	$aContratos = $this->consulta($select, $from, $where, $order);
    	if ($excel) {
    		$this->imprimirExcel($aContratos);
    	} else {
    		if (is_array($aContratos)) {
				$jsonContratos = array();
				foreach ($aContratos as $d) {
					$exp = $d['CodigoExpediente'];
					if ($exp == '') {
						$exp = ''; // para que no salgan los null
					}
					$jsonContratos[] = array(
						'id' => $d['IdUnico'],
						'fecha' => $d['FechaRegistro']->format('d/m/Y'),
						'usuarioDescarga' => ucfirst($d['UsuarioDescarga']),
						'codigoObra' => $d['CodigoObra'],
						'nombreMercantil' => ucfirst($d['NombreMercantil']),
						'obra' => ucfirst($d['NombreObra']),
						'tipo' => ucfirst($d['TipoContrato']),
						'expediente' => $exp
					);
				}
				$aResultados[] = array('jsonContratos' => $jsonContratos);
				return json_encode($aResultados);
			} else {
				return 'false';
			}
    	}
    }

    public function eliminarAdmin($id) {
    	$eliminar = '';
    	if ($id != '') {
    		$from = DB_APP.DB_PREFIJO.'AdministradoresContrato';
    		$where = 'CodigoAdministrador = ' . $id;
    		$eliminar = $this->eliminar($from, $where);
    	}
    	return json_encode(array('eliminado' => $eliminar));
    }

    public function generarDocumento($form) {
    	$tipo = $form['tipo'];
		$fechaNumero = $form['fecha'];
		$fecha = $this->convertirFechaALetra($form['fecha']);
		$cif = $form['cif'];
		$nombre = utf8_decode($form['nombre']);
		$domicilio = utf8_decode($form['domicilio']);
		$cp = $form['codigo_postal'];
		$municipio = utf8_decode($form['municipio']);
		$provincia = utf8_decode($form['provincia']);
		$admin = $form['admin'];
		$admin =  str_replace("Ñ","&Ntilde;",$admin);
		$dnis = $form['dni'];
		$dni = $dnis[0]; // si vuelve a salir el dni, el primero siempre sera para el primer admin, que sera obligatorio
		$cargo = $form['cargo']; // aunque metan varios administradores, van a tener el mismo cargo!
		$cargoUnico = $cargo[0];
		$obra = utf8_decode($form['obra']);
		$obraBD=$form['obra'];
		$forma = $form['forma'];
		$dias = $form['dias'];
		$codigoExpediente = $form['codigo_expediente'];
		$codigoObra = $form['codigo_obra'];
		$sociedad = true; // para subcontratas. Normalmente es sociedad hasta que sea un administrador (si no viene admin pues es autonomo)
		$domicilio = $domicilio.', CP: '.$cp.', Municipio: '.$municipio.', Provincia: '.$provincia;

		/* pueden traer varios admin 16/06/2017 */
		/* el primero lo voy a guardar tal cual por separado en variables, pero los demas voy a hacer la cadena seguida */
		$otrosAdmins = '';
		$contador = 0;
		if (is_array($admin)) {
			foreach ($admin as $a) {
				if ($contador == 0) {
					$admin = $a;
				} else {
					/* si está este admin, va a tener dni tambien, asi que lo cojo directamente del array dnis[] */
					$otrosAdmins .= ', y <strong>D. '. $a .'</strong>, mayor de edad, con DNI ' . $dnis[$contador];
				}
				$contador++;
			}
		} else {
			// si no viene un administrador, es que el nombre de la empresa que puso antes es el nombre del 'administrador' que es autonomo
			$sociedad = false;
		}

		$mercantil_admin = $this->nombreMercantilAdmin($tipo, $nombre, $admin);
		$mercantil_admin_mayus = strtoupper($mercantil_admin);

		/* traer el documento y cambiarle los párrafos */
		$doc = $this->modeloSegunTipo($tipo);
		$nombreDoc = '';

		/* DATOS COMUNES */
		$doc = mb_convert_encoding($doc, 'HTML-ENTITIES', "UTF-8");
		$doc = str_replace("{FECHA_NUMERO}", $fechaNumero, $doc);
		$doc = str_replace("{FECHA}", $fecha, $doc);
		$doc = str_replace("{NOMBRE}", $nombre, $doc); // NOMBRE DE LA EMPRESA (MERCANTIL)
		$doc = str_replace("{DOMICILIO}", $domicilio, $doc);
		$doc = str_replace("{CIF}", $cif, $doc);
		$doc = str_replace("{CARGO}", $cargo, $doc);
		$doc = str_replace("{ADMIN}", $admin, $doc); // NOMBRE DEL ADMINISTRADOR
		$doc = str_replace("{DNI}", $dni, $doc);
		$doc = str_replace("{OBRA}", $obra, $doc); // NOMBRE LARGO DE LA OBRA
		$doc = str_replace("{OTROS_ADMINS}", $otrosAdmins, $doc); // DIRECTAMENTE LA FRASE DE LOS NOMBRES + DNI OTROS ADMIN
		
		/* SUBCONTRATA */
		if ($tipo == "subcontrata_con_aval" || $tipo == "subcontrata_cerrado_con_retenciones" || $tipo == "subcontrata_cerrado_sin_retenciones" || $tipo == "subcontrata_abierto_con_retenciones" || $tipo == "subcontrata_abierto_sin_retenciones") {
			
			$trabajos = utf8_decode($form['trabajos']); // DESCRIPCION DE TRABAJOS
			$importe = $form['importe'];
			$telefono = $form['telefono'];
			$pago_fraccionado = $form['pago_fraccionado'];
			$plazo_estipulado = $form['plazo_estipulado'];
			$fraccionado_descrip = '';
			$fecha_plazo = '';
			$penalizacion = '';

			if ((int)$pago_fraccionado === 1) {
				$fraccionado_descrip = nl2br($form['fraccionado_descrip']);
				$array_lineas = explode('<br />', $fraccionado_descrip);
				$pago_fraccionado_parrafo = 'La forma de pago que establecen las partes para este contrato se establece de la siguiente forma:<br/><ul>';

				foreach ($array_lineas as $linea) {
					$pago_fraccionado_parrafo .= '<li>'.$linea.'</li>';
				}

				$pago_fraccionado_parrafo .= '</ul>';
				$doc = str_replace("{FRACCIONADO_DESCRIP}", $pago_fraccionado_parrafo, $doc);
			} else {
				$doc = str_replace("{FRACCIONADO_DESCRIP}", '', $doc);
			}

			$doc = str_replace("{SOCIEDAD}", $this->autonomoSociedad($sociedad, $admin, $dni, $otrosAdmins, $nombre, $domicilio, $cif), $doc);
			$doc = str_replace("{CODIGO_POSTAL}", $cp, $doc);
			$doc = str_replace("{MUNICIPIO}", $municipio, $doc);
			$doc = str_replace("{TRABAJOS}", $trabajos, $doc);
			$doc = str_replace("{TELEFONO}", $telefono, $doc);
			$doc = str_replace("{FORMAS_PRECIO}", $this->formasPrecio($tipo, $importe), $doc);
			$doc = str_replace("{RETENCION}", $this->comprobarRetencion($tipo), $doc);
			$doc = str_replace("{FORMA_PAGO}", '5&#170;-1.-'.$this->parrafoPago($forma,$dias), $doc);
			$doc = str_replace("{CARGO_UNICO}", $cargoUnico, $doc);

			$nombreDoc = 'subcontrata';
		}

		/* LIQUIDACION */
		if ($tipo == "liquidacion_con_retencion" || $tipo == "liquidacion_con_retencion_autonomo" || $tipo == "liquidacion_sin_retencion" || $tipo == "liquidacion_sin_retencion_autonomo") {
			$fecha_ultima_factura = $form['fecha_ultima_factura'];
			$numero_ultima_factura = $form['numero_ultima_factura'];
			$importe_ultima_factura = $form['importe_ultima_factura'];
			$fecha_contrato_original = $form['fecha_contrato_original'];
			$importe_retencion = $form['importe_retencion'];

			$doc = str_replace("{PARRAFO_ADMIN}", $this->parrafoAdminLiquido($tipo, $admin, $dni, $domicilio, $nombre, $otrosAdmins, $cif), $doc);
			$doc = str_replace("{PARRAFO_TERCERA}", $this->parrafoLiquidacionTercera($tipo, $importe_retencion), $doc);
			$doc = str_replace("{FECHA_ULTIMA_FACTURA}", $fecha_ultima_factura, $doc);
			$doc = str_replace("{NUMERO_ULTIMA_FACTURA}", $numero_ultima_factura, $doc);
			$doc = str_replace("{IMPORTE_ULTIMA_FACTURA}", $importe_ultima_factura, $doc);
			$doc = str_replace("{FECHA_CONTRATO_ORIGINAL}", $fecha_contrato_original, $doc);
			$doc = str_replace("{MERCANTIL}", $mercantil_admin, $doc);

			$nombreDoc = 'liquidación';
		}

		/* SUMINISTRO */
		if ($tipo == "suministro_autonomo" || $tipo == "suministro_sin_autonomo") {
			$fecha_oferta = $form['fecha_oferta'];
			$numero_oferta = $form['numero_oferta'];
			$fecha_suministro_ini = $form['fecha_suministro_ini'];
			$fecha_suministro_fin = $form['fecha_suministro_fin'];

			$doc = str_replace("{PARRAFO_ADMIN_SUMINISTRO}", $this->parrafoAdminSuministro($tipo, $admin, $dni, $domicilio, $nombre, $cif, $otrosAdmins), $doc);
			$doc = str_replace("{MERCANTIL}", $mercantil_admin, $doc);
			$doc = str_replace("{MERCANTIL_MAYUS}", $mercantil_admin_mayus, $doc);
			$doc = str_replace("{NUMERO_OFERTA}", $numero_oferta, $doc);
			$doc = str_replace("{FECHA_OFERTA}", $fecha_oferta, $doc);
			$doc = str_replace("{FORMA_PAGO}", $this->parrafoPago($forma,$dias,$tipo), $doc);
			$doc = str_replace("{FECHA_INICIO}", $fecha_suministro_ini, $doc);
			$doc = str_replace("{FECHA_FIN}", $fecha_suministro_fin, $doc);

			$nombreDoc = 'suministro';
		}

		if ($tipo == "anexo_autonomo" || $tipo == "anexo_sin_autonomo") {
			$fecha_contrato = $form['fecha_anexo_contrato'];
			$doc = str_replace("{PARRAFO_ADMIN_ANEXO}", $this->parrafoAdminAnexo($tipo, $admin, $dni, $domicilio, $nombre, $cif, $otrosAdmins), $doc);
			$doc = str_replace("{FECHA_CONTRATO}", $fecha_contrato, $doc);

			$nombreDoc = 'anexo';
		}

		$nombreDoc = 'Contrato de '.$nombreDoc.' '.$admin.'.doc';

		$firma = 'EL SUBCONTRATISTA';
		if ($tipo == "suministro_autonomo" || $tipo == "suministro_sin_autonomo") {
			$firma = 'EL PROVEEDOR';
		}

		/* guardar descarga */
		$campos = 'CodigoEmpresa, UsuarioDescarga, CifDni, NombreMercantil, CodigoObra, NombreObra, CodigoTipoContrato, TipoContrato, CodigoExpediente';
		$tabla = DB_APP.DB_PREFIJO.'DescargasContratos';
		$valores = "'".$this->empresa."', '" . $this->usuario . "', '" . $cif . "', '" . utf8_encode($nombre) . "', '" . $codigoObra . "', '" . $obraBD . "', '" . $tipo . "', '".$this->nombreSegunTipo(utf8_encode($tipo))."', '" .$codigoExpediente. "'";
		$this->insertar($campos, $tabla, $valores);
		
		$this->descargarDocumento($nombreDoc, $doc, $firma);		
    }

    private function descargarDocumento($nombreDoc, $cuerpo, $firma) {
    	header('Content-type: application/vnd.ms-word; charset=utf-8');
		header("Content-Disposition: attachment;Filename=\"".$nombreDoc."\""); // solo funciona con comillas dobles!!!
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);
		echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>";
		echo '<meta http-equiv=\'Content-Type\' content=\'text/html; charset=Windows-1252\'>';
		echo '<style>
				p.MsoFooter, li.MsoFooter, div.MsoFooter {
		            margin: 0cm;
		            margin-bottom: 0cm;
		            mso-pagination:widow-orphan;
		            font-size: 12.0 pt;
		        }
		        p.MsoFooter {
		            text-align: center;
		        }
		        @page Section1 {
		            margin: 1cm 2cm 0cm 2cm; /*márgenes completos a modificar si salta a otra página*/
		            mso-page-orientation: portrait;
		            mso-footer:f1;
		        }
		        div.Section1 { page:Section1; }
				body{
					font-family:Calibri,sans-serif;
					font-size:9pt;
					text-align:justify
				}
				p.sangria{
					text-indent:3em;
				}
				p.doble_sangria{
					text-indent:4em;
				}
				div{
					margin-top: 20px;
					/*margin:20px 0*/
				}

				.listaguion{
					list-style:none
				}
				.listaguion li:before{
					content:"-"
				}
				table{
					border:1px solid black;
					border-collapse:collapse;
					width:100%;
				}
				table tr td{
					border:1px solid black
				}
				.tablanoborde, .tablanoborde tr td{
					border:0;
					padding-bottom:7px;
				}
				.centrar{
					text-indent:14em;
				}
				table#pie{
					margin:0in 0in 0in 9in;

					/*height: 0px;
					 max-height:0; 
					background-color: green;*/
				}
				#parrafo{
					
				}
				table#pie tr td{
					border:0 !important;
				}

			</style>
			<body>
				<div class="Section1">
					'.$cuerpo.'
		            <table id="pie" class="prueba" border="1" cellspacing="0" cellpadding="0">
			            <tr>
			                <td>
			                    <div style="mso-element: footer" class="firma-pie" id="f1">
			                        <p class="MsoFooter" id="parrafo">
				                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					                    <strong>CHIRIVO CONSTRUCCIONES, S.L.</strong>
					                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					                    <strong>'.$firma.'</strong>
					                    <br/><br/><br/>
			                            <span style="mso-field-code:\' PAGE \'"></span>
			                        </p>
			                    </div>
			                </td>
			            </tr>
			        </table>
			    </div>
		    </body>
		</html>
		'; // tiene que ser con espacios, no se pueden insertar tablas en el footer de un word

		exit;
    }

    public function imprimirExcel($aContratos) {
    	header('Content-type: application/vnd.ms-word; charset=utf-8');
		header("Content-Disposition: attachment;Filename=\"informe.xls\"");
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);

    	$separador = ';';
		$csv = 'sep=;'."\n\n";
		$csv .='Fecha registro'.$separador.'Usuario descarga'.$separador.'Nombre mercantil'.$separador.'Código obra'.$separador.'Obra'.$separador.'Tipo de contrato'.$separador.'Código expediente'.$separador."\n";

		if (is_array($aContratos)) {
			foreach ($aContratos as $c) {
				$csv .=
						'"'		.trim(	$c['FechaRegistro']->format('d-m-Y H:i')		)		.'"'.$separador.''.
						'"'		.trim(	$c['UsuarioDescarga']							)		.'"'.$separador.''.
						'"'		.trim(	$c['NombreMercantil']							)		.'"'.$separador.''.
						'"'		.trim(	$c['CodigoObra']								)		.'"'.$separador.''.
						'"'		.trim(	$c['NombreObra']								)		.'"'.$separador.''.
						'"'		.trim(	$c['TipoContrato']								)		.'"'.$separador.''.
						'"'		.trim(	$c['CodigoExpediente']							)		.'"'.$separador.''.
						"\n";
			}			
		}

		$csv = utf8_decode($csv);
		echo $csv;
		exit;
    }

    private function nombreSegunTipo($tipo) {
		switch ($tipo) {
		    case "subcontrata_con_aval": 				return 'Subcontrata con aval';
		    case "subcontrata_cerrado_con_retenciones": return 'Subcontrata precio cerrado con retenciones';
		    case "subcontrata_cerrado_sin_retenciones": return 'Subcontrata precio cerrado sin retenciones';
		    case "subcontrata_abierto_con_retenciones": return 'Subcontrata precio abierto con retenciones';
		    case "subcontrata_abierto_sin_retenciones": return 'Subcontrata precio abierto sin retenciones';
		    case "suministro_autonomo":					return 'Suministro autónomo';
		    case "suministro_sin_autonomo":				return 'Suministro no autónomo';
		    case "liquidacion_con_retencion":			return 'Liquidación con retenciones';
		    case "liquidacion_con_retencion_autonomo":	return 'Liquidación con retenciones autónomo';
		    case "liquidacion_sin_retencion":			return 'Liquidación sin retenciones';
		    case "liquidacion_sin_retencion_autonomo":	return 'Liquidación sin retenciones autónomo';
		    case "anexo_autonomo":						return 'Anexo autónomo';
		    case "anexo_sin_autonomo":					return 'Anexo no autónomo';
		    default:									return $tipo;
		}
	}

	private function modeloSegunTipo($tipo) {
		switch ($tipo) {
		    case "subcontrata_con_aval": 				return $this->modeloSubcontrata();
		    case "subcontrata_cerrado_con_retenciones": return $this->modeloSubcontrata();
		    case "subcontrata_cerrado_sin_retenciones": return $this->modeloSubcontrata();
		    case "subcontrata_abierto_con_retenciones": return $this->modeloSubcontrata();
		    case "subcontrata_abierto_sin_retenciones": return $this->modeloSubcontrata();
		    case "suministro_autonomo":					return $this->modeloSuministro();
		    case "suministro_sin_autonomo":				return $this->modeloSuministro();
		    case "liquidacion_con_retencion":			return $this->modeloLiquidacion();
		    case "liquidacion_con_retencion_autonomo":	return $this->modeloLiquidacion();
		    case "liquidacion_sin_retencion":			return $this->modeloLiquidacion();
		    case "liquidacion_sin_retencion_autonomo":	return $this->modeloLiquidacion();
		    case "anexo_autonomo":						return $this->modeloAnexo();
		    case "anexo_sin_autonomo":					return $this->modeloAnexo();
		    default:									return $this->modeloSubcontrata();
		}
	}

	private function modeloSubcontrata() {
		$doc = file_get_contents("docs/subcontrata-anexos-general.php");
		return $doc;
	}

	private function modeloSuministro() {
		$doc = file_get_contents("docs/suministro.php");
		return $doc;
	}

	private function modeloLiquidacion() {
		$doc = file_get_contents("docs/liquidacion.php");
		return $doc;
	}

	private function modeloAnexo() {
		$doc = file_get_contents("docs/anexo.php");
		return $doc;
	}

	/* si es autonomo o no, cuando el párrafo dice ENTRE CHIRIVO Y ***** puede ser o el nombre de la mercantil o el nombre del administrador (en caso de autonomo) */
	private function nombreMercantilAdmin($tipo, $mercantil, $admin) {
		switch ($tipo) {
		    case "suministro_autonomo":					return $admin;
		    case "suministro_sin_autonomo":				return $mercantil;
		    case "liquidacion_con_retencion":			return $mercantil;
		    case "liquidacion_con_retencion_autonomo":	return $admin;
		    case "liquidacion_sin_retencion":			return $mercantil;
		    case "liquidacion_sin_retencion_autonomo":	return $admin;
		    default:									return $mercantil;
		}
	}

	/* parrafo del anexo */
	private function parrafoAdminAnexo($tipo, $admin, $dni, $domicilio, $nombre, $otrosAdmins, $cif) {
		$p = '';
		if ($tipo === 'anexo_sin_autonomo') {
			$p = 'Y de otra, <strong>D. '.$admin.'</strong>, mayor de edad, con DNI '.$dni.' y actuando en su calidad de apoderado de la mercantil <strong>'.$nombre.'</strong> con domicilio a efectos de notificaciones en '.$domicilio.' y C.I.F. '.$otrosAdmins.'. En adelante EL SUBCONTRATISTA.';
		} else if ($tipo === 'anexo_autonomo') {
			$p = 'Y de otra, <strong>D. '.utf8_decode($admin).'</strong>, mayor de edad, con DNI '.utf8_decode($dni).', actuando en su propio nombre y derecho y con domicilio a efectos de notificaciones  en '.utf8_decode($domicilio).'. En adelante EL SUBCONTRATISTA.';
		}
		return $p;
	}

	/* parrafo del suministro */
	private function parrafoAdminSuministro($tipo, $admin, $dni, $domicilio, $nombre, $otrosAdmins, $cif) {
		$p = '';
		if ($tipo === 'suministro_sin_autonomo') {
			$p = 'Y de otra, <strong>D. '.$admin.'</strong>, mayor de edad, con DNI '.$dni.$otrosAdmins.' y actuando en su calidad de apoderado de la mercantil <strong>'.$nombre.'</strong> con domicilio a efectos de notificaciones en '.$domicilio.' y C.I.F. '.$cif.'. En adelante EL PROVEEDOR.';
		} else if ($tipo === 'suministro_autonomo') {
			$p = 'Y de otra, <strong>D. '.$admin.'</strong>, mayor de edad, con DNI '.$dni.', actuando en su propio nombre y derecho y con domicilio a efectos de notificaciones  en '.$domicilio.'. En adelante EL PROVEEDOR.';
		}
		return $p;
	}

	/* parrafos del modelo liquidación */
	private function parrafoAdminLiquido($tipo, $admin, $dni, $domicilio, $nombre, $otrosAdmins, $cif) { // se modifica el segundo parrafo del administrador
		$p = '';
		if ($tipo === 'liquidacion_sin_retencion' || $tipo === 'liquidacion_con_retencion') {
			$p = 'Y de otra, <strong>D. '.$admin.'</strong>, mayor de edad, con DNI '.$dni.$otrosAdmins.' y actuando en su calidad de apoderado de la mercantil <strong>'.$nombre.'</strong> con domicilio a efectos de notificaciones en '.$domicilio.' y C.I.F. '.$cif.'. En adelante EL SUBCONTRATISTA.';
		} else if ($tipo === 'liquidacion_sin_retencion_autonomo' || $tipo === 'liquidacion_con_retencion_autonomo') {
			// al ser autonomo, el admin viene vacio, por lo que se escribe el nombre de la mercantil (que sera el del admin realmente)
			$p = 'Y de otra, <strong>D. '.$nombre.'</strong>, mayor de edad, con DNI '.$cif.', actuando en su propio nombre y derecho y con domicilio a efectos de notificaciones  en '.$domicilio.'. En adelante EL SUBCONTRATISTA.';
		}
		return $p;
	}

	private function parrafoLiquidacionTercera($tipo, $importe_retencion) { //tanto sin/con como autonomo o sin autonomo
		$p = '';
		if ($tipo === 'liquidacion_sin_retencion' || $tipo === 'liquidacion_sin_retencion_autonomo' ) {
			$p = ' EL SUBCONTRATISTA declara que no existe ninguna retenci&oacute;n en poder de CHIRIVO CONSTRUCCIONES, S.L, referente a los trabajos realizados, en concreto, la relativa a la garant&iacute;a de la perfecta ejecuci&oacute;n de los trabajos realizados durante el per&iacute;odo de garant&iacute;a de la obra, as&iacute; como de otras potenciales responsabilidades imputables al SUBCONTRATISTA.';
		} else if ($tipo === 'liquidacion_con_retencion' || $tipo === 'liquidacion_con_retencion_autonomo') {
			$p = 'Como &uacute;nica excepci&oacute;n a lo indicado, EL SUBCONTRATISTA declara que en poder de CHIRIVO CONSTRUCCIONES, S.L, existe una retenci&oacute;n del 5%, ascendente a '.$importe_retencion.' &euro;, relativo a los trabajos realizados, en garant&iacute;a de la perfecta ejecuci&oacute;n de los trabajos realizados durante el per&iacute;odo de garant&iacute;a de la obra, as&iacute; como de otras potenciales responsabilidades imputables al SUBCONTRATISTA.';
		}
		return $p;
	}

	/* parrafos del modelo subcontrata segun el tipo (precio abierto/cerrado - con retención o sin) */
	private function parrafoPago($tipo, $dias, $tipoContrato = '') { // formas de pago: confirming, pagaré, trasferencia, contado
		$p = '';
		$nombre = 'SUBCONTRATISTA';
		$objeto = 'certificaciones';
		if ($tipoContrato == 'suministro_autonomo' || $tipoContrato == 'suministro_sin_autonomo') {
			$nombre = 'PROVEEDOR';
			$objeto = 'facturas';
		}
		if ($tipo == 1) {
			$p = 'CHIRIVO CONSTRUCCIONES, S.L abonar&aacute; al '.$nombre.' las '.$objeto.' mensuales de obra ejecutada, mediante confirming a trav&eacute;s de una entidad financiera, con vencimiento el d&iacute;a de pago a los '.$dias.' d&iacute;as de la fecha de recepci&oacute;n factura de conformidad, siendo d&iacute;a de pago el d&iacute;a 25 de cada mes, y si el d&iacute;a fuese s&aacute;bado o festivo, el pr&oacute;ximo d&iacute;a laborable, con excepci&oacute;n del d&iacute;a 25 de Agosto, que pasar&aacute;n al siguiente d&iacute;a de pago.';
		} else if ($tipo == 2) {
			$p = 'CHIRIVO CONSTRUCCIONES, S.L abonar&aacute; al '.$nombre.' las '.$objeto.' mensuales de obra ejecutada, mediante pagar&eacute;s aceptados con vencimientos el d&iacute;a de pago a los '.$dias.' d&iacute;as de la fecha de recepci&oacute;n factura de conformidad, siendo d&iacute;a de pago el 25 de cada mes, y si dicho d&iacute;a fuese s&aacute;bado o festivo, el pr&oacute;ximo d&iacute;a laborable, con excepci&oacute;n del d&iacute;a 25 de Agosto, que pasar&aacute;n al siguiente d&iacute;a de pago.';
		} else if ($tipo == 3) {
			$p = 'CHIRIVO CONSTRUCCIONES, S.L abonar&aacute; al '.$nombre.' las '.$objeto.' mensuales de obra ejecutada, mediante transferencia bancaria al n&uacute;mero de cuenta que facilite el SUBCONTRATISTA y del que sea &eacute;ste titular &uacute;nico; dicha transferencia bancaria se realizar&aacute; a partir de la fecha de recepci&oacute;n factura de conformidad, siendo d&iacute;a de pago el d&iacute;a 25 de cada mes, y si dicho d&iacute;a fuese s&aacute;bado o festivo, el pr&oacute;ximo d&iacute;a laborable, con excepci&oacute;n del d&iacute;a 25 de Agosto, que pasar&aacute;n al siguiente d&iacute;a de pago.';
		} else if ($tipo == 4) {
			$p = 'CHIRIVO CONSTRUCCIONES, S.L abonar&aacute; al '.$nombre.' las '.$objeto.' mensuales de obra ejecutada. Se realizar&aacute; el pago al contado a partir de la fecha de recepci&oacute;n factura de conformidad, siendo d&iacute;a de pago el d&iacute;a 25 de cada mes, y si dicho d&iacute;a fuese s&aacute;bado o festivo, el pr&oacute;ximo d&iacute;a laborable, con excepci&oacute;n del d&iacute;a 25 de Agosto, que pasar&aacute;n al siguiente d&iacute;a de pago.';
		}
		return $p;
	}

	private function autonomoSociedad($sociedad = true, $admin, $dni, $otrosAdmins, $nombreMercantil, $domicilio, $cif) { // o es autonomo o es sociedad en los contratos de subcontrata
		$p = '  <p class="sangria">
					Y de otra, <strong>D. '.$admin.'</strong>, mayor de edad, 
					con DNI '.$dni.''.$otrosAdmins.' y actuando en su calidad de apoderado de la mercantil 
					<strong>'.$nombreMercantil.'</strong> con domicilio a efectos de notificaciones en 
					'.$domicilio.' 
					y C.I.F. '.$cif.'. 
					En adelante EL SUBCONTRATISTA.
				</p>';
		if (!$sociedad) { // es un autonomo
			$p = '  <p class="sangria">
						Y de otra, <strong>D. '.$nombreMercantil.'</strong>, mayor de edad, con DNI '.$cif.', actuando en su propio nombre y derecho y con domicilio a efectos de notificaciones en '.$domicilio.' . En adelante EL SUBCONTRATISTA.
					</p>';
		}	
		return $p;
	}

	private function formasPrecio($tipo, $importe) {
		$p = '';
		// por defecto ponia: DIEZ MIL CUATROCIENTOS NUEVE EUROS CON OCHENTA Y CUATRO C&Eacute;NTIMOS (10.409,84 &euro;)
		if ($tipo === 'subcontrata_cerrado_con_retenciones' || $tipo === 'subcontrata_cerrado_sin_retenciones') {
			// $importeLetras = strtoupper(convertirNumeroALetra($importe, true)); // true = es moneda
			// $importeLetras = strtoupper(convert_number_to_words($importe));
			include_once 'NumeroALetras.class.php';
			$numeroLetras = new NumeroALetras();
			$importeLetras = $numeroLetras->convertir($importe, 'EUROS', 'CÉNTIMOS');
			$importeLetras = utf8_decode(strtoupper($importeLetras));
			$fraseImporte = $importeLetras . ' (' . $importe . ' &euro;)';

			$p = '1&#170;-1.- EL SUBCONTRATISTA acepta llevar a cabo los trabajos a que se refiere su oferta redactada en ANEXO I del presente contrato por un importe de <strong>'.$fraseImporte.', IVA NO incluido</strong>, de acuerdo con el Proyecto de la Obra a que pertenece, que declara conocer, y en las condiciones de su citada oferta, en lo que no resulte modificada por el presente contrato y ANEXO-1.';
		} else if ($tipo === 'subcontrata_abierto_con_retenciones') {
			$p = '1&#170;-1.-El precio del presente contrato resultar&aacute; del producto de multiplicar las mediciones por el precio unitario de cada unidad de obra, de acuerdo con los precios que se adjuntan en el <strong>Anexo n&uacute;m. I</strong> de este contrato. Los trabajos a realizar por el SUBCONTRATISTA deber&aacute;n adecuarse al Proyecto de la Obra a la que pertenece, que declara conocer.';
		}
		return $p;
	}

	private function comprobarRetencion($tipo) {
		switch ($tipo) {
		    case "subcontrata_con_aval": 				return '';
		    case "subcontrata_cerrado_con_retenciones": return '';
		    case "subcontrata_cerrado_sin_retenciones": return 'no ';
		    case "subcontrata_abierto_con_retenciones": return '';
		    case "subcontrata_abierto_sin_retenciones": return 'no ';
		    default:									return '';
		}
	}

	private function parrafoFechaPlazo() { //por defecto
		$p = 'Los trabajos contratados comenzar&aacute;n de inmediato y se finalizar&aacute;n conforme a las necesidades del ritmo de las obras.';
		return $p;
	}

	private function convertirFechaALetra($date) {
		$arrayMeses = array('', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');
		//La fecha se supone en formato dd-mm-yyyy	
		$arrayFecha = explode('/', $date);
		$dia = ltrim($arrayFecha[0], '0'); // Elimino un cero al principio del dia si lo hay
		$mes = ltrim($arrayFecha[1], '0'); // Elimino un cero al principio del mes si lo hay
		$anio = $arrayFecha[2];
		return $this->convertirNumeroALetra($dia).' de '.$arrayMeses[$mes].' de '. $this->convertirNumeroALetra($anio);
	}

	private function convertirNumeroALetra($num, $moneda = false) {
		$arrayUnidades = array('','uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte');
		$arrayDecenas = array('','', 'veinti', 'treinta', 'cuarenta', 'cincuenta', 'sesenta','setenta', 'ochenta', 'noventa');
		$arrayCentenas = array('','ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos');
		$arrayUnidadesMillar = array('', 'mil', 'dos mil', 'tres mil', 'cuatro mil', 'cinco mil', 'seis mil', 'siete mil', 'ocho mil', 'nueve mil');
		
		$resultado = '';
		$arrayNum = str_split($num);

		if (count($arrayNum) === 4) { // Número con 4 cifras	
			$resultado .= $arrayUnidadesMillar[$arrayNum[0]];	
		}
		
		if (count($arrayNum) >= 3) { // Número al menos 3 cifras	
			// Compruebo si las centenas es igual a 100
			if (implode(array_slice($arrayNum, count($arrayNum)-3)) === '100') {
				$resultado .= ' cien';
			} else {
				$resultado .= ' '.$arrayCentenas[$arrayNum[count($arrayNum)-3]];
			}
		}
		
		if (count($arrayNum) >= 2) { // Número al menos 2 cifras	
			// Compruebo si las centenas es menor que 21
			if ((int)implode(array_slice($arrayNum, count($arrayNum)-2)) < 21) {
				$resultado .= ' '.$arrayUnidades[implode(array_slice($arrayNum, count($arrayNum)-2))];
			} else {		
				$resultado .= ' '.$arrayDecenas[$arrayNum[count($arrayNum)-2]];
				$espacio = ' ';	
				if ((int )$arrayNum[count($arrayNum)-2] != 2) {
					$resultado .= ' y';
				} else {
					$espacio = '';
				}
				$resultado .= $espacio.$arrayUnidades[$arrayNum[count($arrayNum)-1]];		
			}
		}
		
		if (count($arrayNum) === 1) { // Número con una cifra
			$resultado .= ' '.$arrayUnidades[$arrayNum[count($arrayNum)-1]];	
		}

		return $resultado;
	}
}
