<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * MamonSoft
 *
 * Kernel
 *
 * @package pace\application\modules\panel\model\beneficiario
 * @subpackage utilidad
 * @author Carlos Peña
 * @copyright Derechos Reservados (c) 2015 - 2016, MamonSoft C.A.
 * @link http://www.mamonsoft.com.ve
 * @since version 1.0
 */

class KCargador extends CI_Model{

  /**
  * @var Nomina
  */
  var $OidNomina = 0;

  /**
  * @var MBeneficiario
  */
  var $Beneficiario = null;


  /**
  * @var KMedidaJudicial
  */
  var $MedidaJudicial = null;



  /**
  * @var Retroactivo
  */
  var $Retroactivos = null;



  /**
  * @var KArchivos
  */
  var $Archivos = null;

  /**
  * @var KResumenPresupuestario
  */
  var $ResumenPresupuestario = array();

  

  

  /**
  * @var Complejidad del Manejador (Driver)
  */
  var $Nivel = 0;

  /**
  * @var array
  */
  var $Resultado = array();

  /**
  * @var double
  */
  var $SSueldoBase = 0.00;

 /**
  * @var double
  */
  var $Neto = 0.00;

  /**
  * @var double
  */
  var $Asignacion = 0.00;

  /**
  * @var double
  */
  var $Deduccion = 0.00;
  

  /**
  * @var double
  */
  var $Incidencias = array();


  /**
  * @var double
  */
  var $Cantidad = 0;

   /**
  * @var double
  */
  var $CantidadSobreviviente = 0;

  /**
  * @var double
  */
  var $Registros = 0;

  /**
  * @var double
  */
  var $Anomalia = 0;

    /**
  * @var double
  */
  var $SinPagos = 0;

    /**
  * @var double
  */
  var $AnomaliaSobreviviente = 0;

  /**
  * @var double
  */
  var $TotalRegistros = 0;

  /**
  * @var double
  */
  var $Paralizados = 0;

    /**
  * @var double
  */
  var $ParalizadosSobrevivientes = 0;

    /**
  * @var double
  */
  var $OperarBeneficiarios = 0;

  /**
  * @var double
  */
  var $SQLMedidaJudicial = "";

  /**
  * @var double
  */
  var $CantidadMedida = 0;

  /**
  * @var double
  */
  var $ComaMedida = "";

    /**
  * @var double
  */
  var $ComaFallecidos = "";
  
  /**
   * @var WNomina
   */
  var $_MapWNomina;

  /**
   * @var Funcion para reflexionar
   */
  var $functionRefelxion;

  /**
   * @var Fallecidos Con Pension (Sobrevivientes)
   */
  var $FCP = array();

  /**
  * Iniciando la clase, Cargando Elementos Pensiones
  *
  * @access public
  * @return void
  */
  public function __construct(){
    parent::__construct();
    if(!isset($this->DBSpace)) $this->load->model('comun/DBSpace');
    $this->load->model('kernel/KCalculo');
    $this->load->model('kernel/KGenerador');
    $this->load->model('kernel/KRecibo');
    $this->load->model('kernel/KReciboSobreviviente');
    
  }


  function ConsultarBeneficiario($id = '', $param = array()){
    $this->load->model('fisico/MBeneficiario');
    $this->MBeneficiario->ObtenerID('');
    $this->KCalculo->Ejecutar($this->MBeneficiario);
    return $this->MBeneficiario;
  }

  /**
   *  Generar archivos para procesos de lotes (Activos)
   *
   *  Creación de tablas para los esquemas space
   * ---------------------------------------------
   *  INICIANDO PROCESOS POR LOTES
   * ---------------------------------------------
   *
   * @return  void
   */
  public function IniciarLote($arr, $archivo, $autor){
    ini_set('memory_limit', '4024M'); //Aumentar el limite de PHP
    ini_set('max_execution_time', 300);

    $this->load->model('comun/Dbpace');
    $this->load->model('kernel/KSensor');
    
    $this->load->model('fisico/MBeneficiario');
    $this->load->model('kernel/KMedidaJudicial');
    $this->load->model('kernel/KArchivos');
    
    $this->MedidaJudicial = $this->KMedidaJudicial->Cargar($this->_MapWNomina["nombre"]);
    $this->Archivos = $this->KArchivos->Cargar($this->_MapWNomina);
    $sConsulta = "
      SELECT
        regexp_replace(bnf.nombres, '[^a-zA-Y0-9 ]', '', 'g') as nombres,
        regexp_replace(bnf.apellidos, '[^a-zA-Y0-9 ]', '', 'g') as apellidos,
        bnf.cedula, fecha_ingreso,f_ult_ascenso, grado.codigo,grado.nombre as gnombre,
        bnf.componente_id, n_hijos, st_no_ascenso, bnf.status_id,
        st_profesion, monto_especial, anio_reconocido, mes_reconocido,dia_reconocido,bnf.status_id as status_id, 
        bnf.porcentaje, f_retiro, bnf.tipo, bnf.banco, bnf.numero_cuenta, bnf.situacion, bnf.f_nacimiento
        FROM
          beneficiario AS bnf
        JOIN
          grado ON bnf.grado_id=grado.codigo AND bnf.componente_id= grado.componente_id
        WHERE 
          bnf.situacion = '" . $this->_MapWNomina["tipo"] . "'
          AND
          bnf.status_id = 201
          -- AND bnf.anio_reconocido > 0 AND bnf.mes_reconocido > 0 AND bnf.dia_reconocido > 0
          -- AND bnf.anio_reconocido IS NULL
          -- AND bnf.cedula IN ( '18214241', '12488541','15683209', '236810', '9698574.','12834431', '11113890', '1636273' )
          -- AND bnf.cedula='7391293' --RCP '4262481' --FCP='15236250' 
          -- grado.codigo NOT IN(8450, 8510, 8500, 8460, 8470, 8480, 5320) 
        ORDER BY grado.codigo
          -- AND grado.codigo IN( 10, 15)
          -- LIMIT 190 OFFSET 10
          -- LIMIT 10";
    
    $con = $this->DBSpace->consultar($sConsulta);
    $this->functionRefelxion = "generarConPatrones";
    $strNombre = $this->_MapWNomina["nombre"];
    if($this->_MapWNomina["tipo"] == "FCP"){
      $this->cargarFamiliaresFCP();
      if( $strNombre == "DIFERENCIA DE SUELDO" ||  
          $strNombre == "DIFERENCIA DE RETRIBUCION ESPECIAL" || 
          $strNombre == "DIFERENCIA DE BONO" || 
          $strNombre == "NOMINA DE RETROACTIVOS"
          ){

        $this->functionRefelxion = "generarConPatronesFCPDIF";

      }else if( $strNombre == "RETRIBUCION ESPECIAL"  || 
                $strNombre == "BONO RECREACIONAL" || 
                $strNombre == "PAGO ESPECIAL ( BONO )"){

        $this->functionRefelxion = "generarConPatronesFCPRetribucionEspecial";

      }else if ($strNombre == "AGUINALDOS" ) {
        $this->functionRefelxion = "gCPatronesFCPAguinaldos";
      }else{
        $this->functionRefelxion = "generarConPatronesFCP";
      }
      
    }else{ //DE LO CONTRARIO EN CASO DE NOMINA PARA RCP
      if( $strNombre == "DIFERENCIA DE SUELDO" ||  
          $strNombre == "DIFERENCIA DE RETRIBUCION ESPECIAL" ||
          $strNombre == "DIFERENCIA DE BONO" || 
          $strNombre == "NOMINA DE RETROACTIVOS" ){
        $this->functionRefelxion = "generarConPatronesRCPDIF";
        
      }else if( $strNombre == "RETRIBUCION ESPECIAL"  || $strNombre == "BONO RECREACIONAL" || $strNombre == "PAGO ESPECIAL ( BONO )"){
        $this->functionRefelxion = "generarConPatronesRetribucionEspecial";
      }
    }
    $this->asignarBeneficiario($con->rs, $arr['id'], $arr['fecha'], $archivo, $autor);
  }


  public function asignarBeneficiario($obj, $id, $fecha, $archivo, $autor){
    $this->load->model('kernel/KCalculoLote');
    $this->load->model('kernel/KDirectiva');
    $this->load->model('kernel/KNomina');
    $Directivas = $this->KDirectiva->Cargar($id); //Directivas
    $this->load->model('kernel/KPerceptron'); //Red Perceptron Aprendizaje de patrones
    $this->load->model('kernel/KAsignaciones'); //Red Perceptron Aprendizaje de patrones
    $this->KNomina->Cargar( $this->_MapWNomina );
    $this->Retroactivos = $this->KAsignaciones->Cargar( $this->_MapWNomina );
    // semanage fcontext -a -t httpd_sys_rw_content_t '/var/www/html/SSSIFANBW/pensiones/tmp'
    // restorecon -v '/var/www/html/SSSIFANBW/pensiones/tmp'
    // setsebool -P httpd_unified 1
    // ausearch -c 'httpd' --raw | audit2allow -M mi-httpd
    // semodule -i mi-httpd.pp
    $file = fopen("tmp/" . $archivo . ".csv","a") or die("Problemas");//Para Generar archivo csv 04102017
    $file_sqlCVS = fopen("tmp/" . $archivo . "-SQL.sql","a") or die("Problemas");//Para Generar archivo csv 04102017
    $file_log = fopen("tmp/" . $archivo . "-ERR.csv","a") or die("Problemas");
    $file_medida = fopen("tmp/" . $archivo . "-MJ.sql","a") or die("Problemas");
    $file_cajas = fopen("tmp/" . $archivo . "-CA.sql","a") or die("Problemas");

    $linea = 'CEDULA;APELLIDOS;NOMBRES;TIPO;BANCO;NUMERO CUENTA;FECHA INGRESO;FECHA ASCENSO;FECHA RETIRO;COMPONENTE;GRADO;GRADO DESC.;TIEMPO DE SERV.;';
    $linea .= 'ANTIGUEDAD;NUM. HIJOS;PORCENTAJE;FECHA_NACIMIENTO;';
    
    

    $sqlMJ = "INSERT INTO space.medidajudicial_detalle ( nomi, cedu, cben, bene, caut, naut, inst, tcue, ncue, pare, crea, usua, esta, mont ) VALUES ";
    $sqlCVS = "INSERT INTO space.pagos ( nomi, did, cedu, nomb, calc, fech, banc, nume, tipo, situ, esta, usua, neto, base, grad, caut, naut, cfam, pare ) VALUES ";
    
    $cant = count($this->_MapWNomina['Concepto']);
    $map = $this->_MapWNomina['Concepto'];
    $medida_str = "";
    $cajaahorro_str = "";
    $fcplinea = "";
    $fcplinea_aux = '';
    for ($i= 0; $i < $cant; $i++){
        $rs = strtoupper($map[$i]['codigo']);
        if($this->_MapWNomina['tipo'] == "FCP"){ 
          $nmb = strtoupper($map[$i]['nombre']);
          if( $nmb == "SUELDO BASE" || $nmb == "DIRECTIVA PRIMAS" || $nmb == "PENSION") {
            $fcplinea .= $rs . ";";
          }else{
            $fcplinea_aux .= $rs . ";";
          }
        }else{
          $linea .= $rs . ";";
        }
    }
    $linea .= $medida_str . $cajaahorro_str . 'ASIGNACION;DEDUCCION;NETO';

    

    if($this->_MapWNomina['tipo'] == "FCP"){
      $linea = 'CEDULA;APELLIDOS;NOMBRES;';
      $linea .= 'FECHA INGRESO;FECHA ASCENSO;FECHA RETIRO;COMPONENTE;GRADO;GRADO DESC.;';
      $linea .= 'TIEMPO DE SERV.;ANTIGUEDAD;NUM. HIJOS;PORCENTAJE;';
      $linea .= $fcplinea;
      if ($this->_MapWNomina["nombre"] == "AGUINALDOS"){
        $linea .= 'AGUINALDOS;';
      }
      $linea .= 'CEDULA;APELLIDOS;NOMBRES;F.NACIMIENTO;PARENTESCO;TIPO;BANCO;NUMERO CUENTA;PENSION MIL;';
      $linea .= 'PORCENTAJE;';
      $linea .= 'RETROACTIVO;';
      $linea .= 'ASIGNACION;FCIS;FCIR;FAMAY;'. $fcplinea_aux .'NETO';
    }
    fputs($file,$linea);//Para Generar archivo csv 04102017
    fputs($file,"\n");//Salto de linea

    fputs($file_log, $linea . ";DESCRIPCION");//Para Generar archivo de log
    fputs($file_log, "\n");//Salto de linea


    fputs($file_sqlCVS, $sqlCVS);//INSERT SPACE.PAGOS
    fputs($file_medida, $sqlMJ);//INSERT SPACE.MEDIDAJUDICIALES    
    
    $funcion = $this->functionRefelxion;  
    $coma = "";
    $linea = '';
    foreach ($obj as $k => $v) {
      $Bnf = new $this->MBeneficiario;
      $this->KCalculoLote->Instanciar($Bnf, $Directivas);
      $linea = $this->$funcion($Bnf,  $this->KCalculoLote, $this->KPerceptron, $fecha, $Directivas, $v, $this->KNomina->ID);
      if( $Bnf->estatus_activo != '201' ){
        $this->Paralizados++;
      }

      $this->Cantidad++;      
      if($linea["csv"] != ""){
        if( $this->_MapWNomina['tipo'] == "FCP" ){
          fputs($file, $linea["csv"]); //Generacion CSV -> EXCEL
        }else{
          fputs($file,$linea["csv"]); //Generacion CSV -> EXCEL
          fputs($file,"\n");
        }
        
        /** INSERT PARA POSTGRES CIERRE DE LA NOMINA  */
        if ( $this->Cantidad > 1 ){
          $coma = ",";
        }
        if($this->_MapWNomina['tipo'] == "FCP"){
          $lineaSQL = $linea["sql"]; //INSERT PARA SPACE.PAGOS
        }else{
          $lineaSQL = $coma . $linea["sql"]; //INSERT PARA SPACE.PAGOS
        }
        fwrite( $file_sqlCVS, $lineaSQL);
        fputs( $file_medida, $this->SQLMedidaJudicial); //INSERT PARA SPACE.MEDIDAJUDICIAL_DETALLES
        $this->SQLMedidaJudicial = "";

        
       
        
      }else{
        if($linea["log"] != "" && $this->_MapWNomina['tipo'] == "FCP"){
          fputs($file_log, $linea["log"]); //CREACION DE INCIDENCIAS
        }else{
          $lin = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres . PHP_EOL;
          fputs($file_log, $lin); //CREACION DE INCIDENCIAS
        }      
        $this->Cantidad--;
      }
      if ($Bnf->grado_codigo == 0){
        $linea = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres ;
        fputs($file_log, $linea); //CREACION DE INCIDENCIAS
        fputs($file_log, "\n");
        $this->Cantidad--;
      }

    }
    
    $this->OidNomina = $this->KNomina->ID;
    $this->KNomina->Nombre = $archivo;
    $this->KNomina->Monto = $this->Neto;
    $this->KNomina->Tipo = $this->_MapWNomina["tipo"];
    $this->KNomina->Estatus = 1;
    $this->KNomina->Asignacion = $this->Asignacion;
    $this->KNomina->Deduccion = $this->Deduccion;
    $this->KNomina->Cantidad = $this->Cantidad;
    if( $this->_MapWNomina['tipo'] == "FCP" ){
      $this->KNomina->Cantidad = $this->OperarBeneficiarios;
      $this->Cantidad = $this->OperarBeneficiarios;
      $this->Anomalia = $this->AnomaliaSobreviviente;
      $this->Paralizados = $this->ParalizadosSobrevivientes;
    }else if( $this->_MapWNomina["nombre"] == "DIFERENCIA DE SUELDO" ){
      $this->KNomina->Cantidad = $this->OperarBeneficiarios;
      $this->Cantidad = $this->OperarBeneficiarios;
    }
    $this->KNomina->Actualizar();
    $this->KNomina->RegistrarDetalle($this->OidNomina , $this->ResumenPresupuestario);
    fclose($file);//Para Generar archivo csv 04102017
    return true;
  }


  /**
  * Generar Codigos por Patrones en la Red de Inteligencia
  *
  * @param MBeneficiario
  * @param KCalculoLote
  * @param KPerceptron
  * @param KDirectiva
  * @param object
  * @return void
  */
  private function generarConPatrones(MBeneficiario &$Bnf, KCalculoLote &$CalculoLote, KPerceptron &$Perceptron, $fecha, $Directivas, $v, $sqlID){
      ini_set('memory_limit', '3024M');
      $Bnf->cedula = $v->cedula;
      $Bnf->deposito_banco = ""; //$v->cap_banco; //Individual de la Red
      $Bnf->apellidos = $v->apellidos; //Individual del Objeto
      $Bnf->nombres = $v->nombres; //Individual del Objeto
      
      $Bnf->fecha_ingreso = $v->fecha_ingreso;
      $Bnf->fecha_nacimiento = $v->f_nacimiento;
      $Bnf->adultoMayor = $this->Adulto_Mayor($v->f_nacimiento);

      $Bnf->numero_hijos = $v->n_hijos;
      $Bnf->tipo = $v->tipo;
      $Bnf->banco = $v->banco;
      $Bnf->numero_cuenta = $v->numero_cuenta;
      $Bnf->situacion = $v->situacion;
      $Bnf->no_ascenso = $v->st_no_ascenso;
      $Bnf->componente_id = $v->componente_id;
      $Bnf->componente_nombre = $Directivas['com'][$v->componente_id];
      $Bnf->grado_codigo = $v->codigo;
      $Bnf->grado_nombre = $v->gnombre;
      $Bnf->fecha_ultimo_ascenso = $v->f_ult_ascenso;
      $Bnf->fecha_retiro = $v->f_retiro;
      $Bnf->prima_profesionalizacion_mt =  $v->st_profesion;
      $Bnf->estatus_profesion = $v->st_profesion;
      $Bnf->porcentaje = $v->porcentaje;
      

      $Bnf->prima_especial = $v->monto_especial;
      $Bnf->ano_reconocido = $v->anio_reconocido;
      $Bnf->mes_reconocido = $v->mes_reconocido;
      $Bnf->dia_reconocido = $v->dia_reconocido;
      $Bnf->estatus_activo = $v->status_id;
      $asignacion = 0;
      $deduccion = 0;
      $neto = 0;
      $medida_str = "";
      $cajaahorro_str = "";
      $abreviatura = "";
      $linea = '';
      $registro = '';
      $log = '';
      $patron = md5($v->fecha_ingreso.$v->f_retiro.$v->n_hijos.$v->st_no_ascenso.$v->componente_id.
        $v->codigo.$v->f_ult_ascenso.$v->st_profesion.
        $v->anio_reconocido.$v->mes_reconocido.$v->dia_reconocido.$v->porcentaje);

      $cant = count($this->_MapWNomina['Concepto']);
      $map = $this->_MapWNomina['Concepto'];
      $recibo_de_pago = array(); // Contruir el recibo de pago para un JSON
      $segmentoincial = '';  
      //GENERADOR DE CALCULOS DINAMICOS
      if(!isset($Perceptron->Neurona[$patron])){
        $CalculoLote->Ejecutar();
        
        $medida = $this->calcularMedidaJudicial($this->KMedidaJudicial,  $Bnf, $sqlID, $Directivas);
        $cajaahorro = 0; 
        //Aplicar conceptos de Asignación
        for ($i= 0; $i < $cant; $i++){
          $rs = $map[$i]['codigo'];
         
          if (isset( $Bnf->Concepto[$rs] )) {
            $concp = $Bnf->Concepto[$rs];

            switch ( $concp['TIPO'] ) {
              case 2: //LEER ARCHIVOS POR ASIGNACION
                $monto_aux = $this->obtenerArchivos($Bnf, $rs);
                $segmentoincial .=  $monto_aux . ";";
                $asignacion += $monto_aux;
                if($monto_aux != 0)$recibo_de_pago[] = array('desc' =>  $rs, 'tipo' => $concp['TIPO'], 'mont' => $monto_aux);
                //asgnar prepuesto
                $this->asignarPresupuesto( $rs, $monto_aux, $concp['TIPO'], $concp['ABV'], $concp['part'], $concp['cuen'], $concp['codi'] );
                break;
              case 3: //LEER ARCHIVOS POR DEDUCCION               
                $monto = $this->obtenerArchivos($Bnf, $rs);
                $monto_aux = $monto<0?$monto*-1:$monto;
                $segmentoincial .=  $monto_aux . ";";
                $deduccion += $monto_aux;
                if($monto_aux != 0)$recibo_de_pago[] = array('desc' =>  $rs, 'tipo' => $concp['TIPO'], 'mont' => $monto_aux);
                //asgnar prepuesto
                $this->asignarPresupuesto($rs, $monto_aux, $concp['TIPO'], $concp['ABV'], $concp['part'], $concp['cuen'], $concp['codi'] );
                break;
              case 33: //RETROACTIVOS
                $monto = 0;
                if ( isset( $this->Retroactivos[$Bnf->cedula][$rs] )){
                  $retroactivo = $this->Retroactivos[$Bnf->cedula][$rs];
                  $valor = 0;
                  try {
                    //eval('$valor = ' . $fnx); 
                    $fn = $retroactivo['fnxc'];
                    eval('$valor = ' . $fn);
                  } catch (ParseError $e) {
                      // Report error somehow
                  }
                  $monto = $valor;
                }
                $segmentoincial .=  $monto . ";";
                $asignacion += $monto;
                if($monto != 0)$recibo_de_pago[] = array('desc' =>  $rs, 'tipo' => $concp['TIPO'], 'mont' => $monto);
                //asgnar prepuesto
                $this->asignarPresupuesto($rs, $monto, $concp['TIPO'], $concp['ABV'], $concp['part'], $concp['cuen'], $concp['codi'] );
                break;
              case 99: //MEDIDA JUDICIAL
                //$medida_str = $medida[0] . ";";
                $segmentoincial .=  $medida[0] . ";";
                $deduccion +=  $medida[0]; 
                $abreviatura = $concp['ABV'];
                if($medida[0] != 0)$recibo_de_pago[] = array('desc' =>  $medida[1], 'tipo' => 99,'mont' => $medida[0]);
                $this->asignarPresupuesto($rs, $medida[0], '99', $abreviatura, $concp['part'], $concp['cuen'], $concp['codi'] ); 
                break;
              case 98: // CAJA DE AHORRO
                //$cajaahorro_str = $cajaahorro . ";";
                $segmentoincial .= $cajaahorro . ";";
                $deduccion +=  $cajaahorro;
                $abreviatura = $concp['ABV'];
                if($cajaahorro != 0)$recibo_de_pago[] = array('desc' =>  $abreviatura, 'tipo' => 98,'mont' => $cajaahorro);  
                $this->asignarPresupuesto( $rs, $cajaahorro, '99', $abreviatura, $concp['part'], $concp['cuen'], $concp['codi'] );      
                break;
              case 96:
                
                break;
              
              default:
                $monto_aux = $concp['mt'];
                $segmentoincial .=  $monto_aux . ";";
                $asignacion += $concp['TIPO'] == 1? $monto_aux: 0;
                $deduccion += $concp['TIPO'] == 0? $monto_aux: 0;
                if($monto_aux != 0)$recibo_de_pago[] = array('desc' =>  $rs, 'tipo' => $concp['TIPO'], 'mont' => $monto_aux);
                //asgnar prepuesto
                $this->asignarPresupuesto($rs, $concp['mt'], $concp['TIPO'], $concp['ABV'], $concp['part'], $concp['cuen'], $concp['codi'] );
                break;
            }
            

          }else{
            $segmentoincial .= "0;";
          }
        }        
        
        
        
        
        $recuerdo = $Bnf->fecha_ingreso . ';' . $Bnf->fecha_ultimo_ascenso . 
            ';' . $Bnf->fecha_retiro . ';' . $Bnf->componente_nombre . ';' . $Bnf->grado_codigo . 
            ';' . $Bnf->grado_nombre . ';' . $Bnf->tiempo_servicio . ';' . $Bnf->antiguedad_grado . 
            ';' . $Bnf->numero_hijos . ';' . $Bnf->porcentaje ;

        $Perceptron->Aprender($patron, array(
          'RECUERDO' => $recuerdo,
          'ASIGNACION' => $asignacion,
          'DEDUCCION' => $deduccion,
          'SUELDOBASE' => $Bnf->sueldo_base,
          'PENSION' => $Bnf->pension,
          'PORCENTAJE' => $Bnf->porcentaje,
          'CONCEPTO' => $Bnf->Concepto,
          'RECIBO' => $recibo_de_pago
          ) );

          $segmentoincial = $recuerdo . ';' . $Bnf->fecha_nacimiento . ';' . $segmentoincial;

               
        $neto = $asignacion - $deduccion;
        if( $Bnf->situacion == "PG" ){
          $deduccion = 0;          
          if($this->_MapWNomina["nombre"] == "AGUINALDOS"){
            //$asignacion = round(($Directivas['salario'] * 5.66666666 ) /4 , 2);
            $sueldo_mensual = $Directivas['salario'];
            //round(577777.78, 2) ;//
            $asignacion =  round((((((12* $sueldo_mensual)+( 40*( $sueldo_mensual/30))+( 120*( $sueldo_mensual/30)))/12) /30)*30)*1, 2);
            $neto = $asignacion;
            $this->asignarPresupuesto("AGUI0001", $neto, 1, "", "40701010101","", "AGUI0001");
                
          }else{
            $asignacion = $Directivas['salario'];
            $neto = $Directivas['salario'];
          }
          
        }


        if ($Bnf->sueldo_base > 0 && $Bnf->porcentaje > 0 && $asignacion > 0 ){
          $linea = $Bnf->cedula . ';' . trim($Bnf->apellidos) . ';' . trim($Bnf->nombres) . 
           ';' .  $Bnf->tipo . ";'" . $Bnf->banco . ";'" . $Bnf->numero_cuenta . 
           ";" . $this->generarLinea($segmentoincial) . $medida_str . 
           $cajaahorro_str . $asignacion . ';' . $deduccion . ';'  . $neto;
        
        
        }else{
          $log = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres . ';';
         
        }

        
        $this->KRecibo->conceptos = $recibo_de_pago;        
        $this->KRecibo->asignaciones = $asignacion;
        $this->KRecibo->deducciones = $deduccion;
        //Insert a Postgres
        $base = $Bnf->porcentaje . "|" . $Bnf->componente_id . "|" . $Bnf->grado_codigo . "|" . $Bnf->grado_nombre; 
        $registro = "(" . $sqlID . "," . $Directivas['oid'] . ",'" . $Bnf->cedula . 
        "','" . trim($Bnf->apellidos) . ", " . trim($Bnf->nombres) . "','" . 
        json_encode($this->KRecibo) . "',Now(),'" . $Bnf->banco . "','" . $Bnf->numero_cuenta . 
        "','" . $Bnf->tipo . "','" . $Bnf->situacion . "'," . $Bnf->estatus_activo . 
        ",'SSSIFANB'," . $neto . ", '" . $base . "','" . $Bnf->grado_nombre . "','','','','TI')";

      }else{      //En el caso que exista el recuerdo en la memoria   
        $medida = $this->calcularMedidaJudicial($this->KMedidaJudicial,  $Bnf,  $sqlID, $Directivas);
        $cajaahorro = ''; // $this->obtenerCajaAhorro(  $Bnf );

        $deduccion = 0; //$Perceptron->Neurona[$patron]["DEDUCCION"];
        $asignacion = 0; //$Perceptron->Neurona[$patron]["ASIGNACION"];
        $NConcepto = $Perceptron->Neurona[$patron]["CONCEPTO"];
        
        for ($i= 0; $i < $cant; $i++){
          $result = $map[$i]['codigo'];
          if (isset($NConcepto[$result])) {
            $concp = $NConcepto[$result];

            switch ( $concp['TIPO'] ) {
              case 2: //Leer archivos de texto
                $monto_aux = $this->obtenerArchivos($Bnf, $result);
                $segmentoincial .=  $monto_aux . ";";
                $asignacion += $monto_aux;
                if($monto_aux != 0)$recibo_de_pago[] = array('desc' =>  $result, 'tipo' => $concp['TIPO'], 'mont' => $monto_aux);
                //asgnar prepuesto
                $this->asignarPresupuesto($result, $monto_aux, $concp['TIPO'], $concp['ABV'], $concp['part'],$concp['cuen'], $concp['codi']);
                break;
              case 3: //Leer archivos de texto                
                $monto = $this->obtenerArchivos($Bnf, $result);
                $monto_aux = $monto<0?$monto*-1:$monto;
                $segmentoincial .=  $monto_aux . ";";
                $deduccion += $monto_aux;
                if($monto_aux != 0)$recibo_de_pago[] = array('desc' =>  $result, 'tipo' => $concp['TIPO'], 'mont' => $monto_aux);
                //asgnar prepuesto
                $this->asignarPresupuesto($result, $monto_aux, $concp['TIPO'], $concp['ABV'], $concp['part'],$concp['cuen'], $concp['codi']);
                break;
              case 33:
                $monto = 0;
                if ( isset($this->Retroactivos[$Bnf->cedula][$result])){
                  $retroactivo = $this->Retroactivos[$Bnf->cedula][$result];                 
                  $valor = 0;
                  try {                    
                    $fn = $retroactivo['fnxc'];
                    eval('$valor = ' . $fn);
                  } catch (ParseError $e) {
                      // Report error somehow
                  }

                  $monto = $valor;
                }
                $segmentoincial .=  $monto . ";";
                $asignacion += $monto;
                if($monto != 0)$recibo_de_pago[] = array(
                  'desc' =>  $result, 
                  'tipo' => $concp['TIPO'], 
                  'mont' => $monto
                );
                //asgnar prepuesto
                $this->asignarPresupuesto($result, $monto, $concp['TIPO'], $concp['ABV'], $concp['part'],$concp['cuen'], $concp['codi']);
                break;
              case 99:
                $segmentoincial .= $medida[0] . ";";
                $deduccion +=  $medida[0];
                $abreviatura = $concp['ABV'];
                if($medida[0] != 0)$recibo_de_pago[] = array('desc' =>  $medida[1], 'tipo' => 99,'mont' => $medida[0]);
                $this->asignarPresupuesto( $result, $medida[0], '99', $abreviatura, $concp['part'],$concp['cuen'], $concp['codi']);
                break;

                
              
              case 98:
                $segmentoincial .= $cajaahorro . ";";
                //$cajaahorro_str = $cajaahorro . ";";
                $deduccion +=  $cajaahorro;
                $abreviatura = $concp['ABV'];
                if($cajaahorro != 0)$recibo_de_pago[] = array('desc' =>  $abreviatura, 'tipo' => 98,'mont' => $cajaahorro);
                $this->asignarPresupuesto($result, $cajaahorro, '98', $abreviatura, $concp['part'],$concp['cuen'], $concp['codi']); 
                break;
              
              default:
                $monto_aux = $concp['mt'];
                $segmentoincial .=  $monto_aux . ";";
                $asignacion += $concp['TIPO'] == 1? $monto_aux: 0;
                $deduccion += $concp['TIPO'] == 0? $monto_aux: 0;
                if($monto_aux != 0)$recibo_de_pago[] = array('desc' =>  $result, 'tipo' => $concp['TIPO'], 'mont' => $monto_aux);
                //asgnar prepuesto
                $this->asignarPresupuesto($result, $concp['mt'], $concp['TIPO'], $concp['ABV'], $concp['part'],$concp['cuen'], $concp['codi']);
                break;          
            } // Fin de Switch           
          }else{
            $segmentoincial .= "0;";
          } // Fin de si

        } //Fin de repitas

        $neto = $asignacion - $deduccion;

        if( $Bnf->situacion == "PG" ){
          $deduccion = 0;
          //print_r("Pasando" + $this->_MapWNomina["nombre"]);
          if($this->_MapWNomina["nombre"] == "AGUINALDOS"){
            $sueldo_mensual = $Directivas['salario'];
            //$asignacion =  round(($Directivas['salario'] * 5.66666666 ) /4 , 2);
            $asignacion =  round((((((12* $sueldo_mensual)+( 40*( $sueldo_mensual/30))+( 120*( $sueldo_mensual/30)))/12) /30)*30)*1, 2);
            $neto = $asignacion;
            
            $this->asignarPresupuesto("AGUI0001", $neto,  1, "", "40701010101","", "AGUI0001");
                
          }else{
            $asignacion = $Directivas['salario'];
            $neto = $Directivas['salario'];
          }
          
        }
        if($Perceptron->Neurona[$patron]["SUELDOBASE"] > 0   && $Perceptron->Neurona[$patron]["PORCENTAJE"] > 0  && $asignacion > 0 ){
          
          $linea = $Bnf->cedula . ';' . trim($Bnf->apellidos) . ';' . trim($Bnf->nombres) . 
          ';' .  $Bnf->tipo . ";'" . $Bnf->banco . ";'" . $Bnf->numero_cuenta . 
          "\";" . $Perceptron->Neurona[$patron]["RECUERDO"] . ";" . $Bnf->fecha_nacimiento . ";" . $segmentoincial .
          $medida_str . $cajaahorro_str . $asignacion . ';' . $deduccion . ';' . $neto;
        }else{
          $log = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres . ';';
        
        }
        
        $this->KRecibo->conceptos = $recibo_de_pago;
        $this->KRecibo->asignaciones = $asignacion;
        $this->KRecibo->deducciones = $deduccion;
        //Insert a Postgres
        $base = $Bnf->porcentaje . "|" . $Bnf->componente_id . "|" . $Bnf->grado_codigo . "|" . $Bnf->grado_nombre;
        $registro = "(" . $sqlID . "," . $Directivas['oid'] . ",'" . $Bnf->cedula . 
        "','" . trim($Bnf->apellidos) . ", " . trim($Bnf->nombres) . "','" . 
        json_encode($this->KRecibo) . "',Now(),'" . $Bnf->banco . "','" . $Bnf->numero_cuenta . 
        "','" . $Bnf->tipo . "', '" . $Bnf->situacion . "', " . $Bnf->estatus_activo . 
        ", 'SSSIFANB'," . $neto . ",'" . $base . "','" . $Bnf->grado_nombre . "','','','', 'TI')";
        
      }

      $this->SSueldoBase += $Bnf->sueldo_base;
      $this->Asignacion += $asignacion;
      $this->Deduccion += $deduccion;
      $this->Neto += $neto;
      $obj["csv"] = $linea;
      $obj["sql"] = $registro;
      $obj["log"] = $log;
      return $obj;

  }




  /**
  * DIFERENCIAS DE SUELDO PARA RETIRADOS CON PENSION
  *
  * @param MBeneficiario
  * @param KCalculoLote
  * @param KPerceptron
  * @param KDirectiva
  * @param object
  * @return void
  */
  private function generarConPatronesRCPDIF(MBeneficiario &$Bnf, KCalculoLote &$CalculoLote, KPerceptron &$Perceptron, $fecha, $Directivas, $v, $sqlID){
    $Bnf->cedula = $v->cedula;
    $Bnf->deposito_banco = ""; //$v->cap_banco; //Individual de la Red
    $Bnf->apellidos = $v->apellidos; //Individual del Objeto
    $Bnf->nombres = $v->nombres; //Individual del Objeto
    
    $Bnf->fecha_ingreso = $v->fecha_ingreso;
    $Bnf->fecha_nacimiento = $v->f_nacimiento;
    $Bnf->adultoMayor = $this->Adulto_Mayor($v->f_nacimiento);
    $Bnf->numero_hijos = $v->n_hijos;
    $Bnf->tipo = $v->tipo;
    $Bnf->banco = $v->banco;
    $Bnf->numero_cuenta = $v->numero_cuenta;
    $Bnf->situacion = $v->situacion;
    $Bnf->no_ascenso = $v->st_no_ascenso;
    $Bnf->componente_id = $v->componente_id;
    $Bnf->componente_nombre = $Directivas['com'][$v->componente_id];
    $Bnf->grado_codigo = $v->codigo;
    $Bnf->grado_nombre = $v->gnombre;
    $Bnf->fecha_ultimo_ascenso = $v->f_ult_ascenso;
    $Bnf->fecha_retiro = $v->f_retiro;
    $Bnf->prima_profesionalizacion_mt =  $v->st_profesion;
    $Bnf->estatus_profesion = $v->st_profesion;
    $Bnf->porcentaje = $v->porcentaje;
    

    $Bnf->prima_especial = $v->monto_especial;
    $Bnf->ano_reconocido = $v->anio_reconocido;
    $Bnf->mes_reconocido = $v->mes_reconocido;
    $Bnf->dia_reconocido = $v->dia_reconocido;
    $Bnf->estatus_activo = $v->status_id;
    $asignacion = 0;
    $deduccion = 0;
    $neto = 0;
    $medida_str = "";
    $cajaahorro_str = "";
    $abreviatura = "";
    $linea = '';
    $registro = '';
    $log = '';
   
    $cant = count($this->_MapWNomina['Concepto']);
    $map = $this->_MapWNomina['Concepto'];
    $recibo_de_pago = array(); // Contruir el recibo de pago para un JSON

    //GENERADOR DE CALCULOS DINAMICOS
    $segmentoincial = '';    
    $asignacion = 0;
    $deduccion = 0;
    $sueldo_mensual = 0;
    $monto_str = '';
    
    for ($i= 0; $i < $cant; $i++){
      $rs = $map[$i]['codigo'];
      $monto = $this->obtenerArchivos($Bnf, $rs);
      if($monto > 0 ){
        $monto_str .= $monto . ';';
        $asignacion += $monto;

        if($monto != 0)$recibo_de_pago[] = array( 'desc' => $map[$i]['nombre'], 'tipo' => 1, 'mont' => $monto );  
        $this->asignarPresupuesto( $rs, $asignacion,  '1', $map[$i]['nombre'], $map[$i]['partida'], '',  $map[$i]['codigo']);        
      }else{
        $valor = $monto * -1 ;
        $monto_str .= $valor . ';';
        $deduccion +=  $valor;
        
        if($valor != 0)$recibo_de_pago[] = array( 'desc' => $map[$i]['nombre'], 'tipo' => 3, 'mont' => $valor );
        
        $this->asignarPresupuesto( $rs, $deduccion, '3', $map[$i]['nombre'], $map[$i]['partida'], '',  $map[$i]['codigo']);
      }
    } 
  
      
    $segmentoincial = $Bnf->fecha_ingreso . ';' . $Bnf->fecha_ultimo_ascenso . 
        ';' . $Bnf->fecha_retiro . ';' . $Bnf->componente_nombre . ';' . $Bnf->grado_codigo . 
        ';' . $Bnf->grado_nombre . ';' . $Bnf->tiempo_servicio . ';' . $Bnf->antiguedad_grado . 
        ';' . $Bnf->numero_hijos . ';' . $Bnf->porcentaje . ';' . $monto_str;

    
    $neto = $asignacion - $deduccion;
    
    if ($asignacion > 0 ){
        $linea = $Bnf->cedula . ';' . trim($Bnf->apellidos) . ';' . trim($Bnf->nombres) . 
        ';' .  $Bnf->tipo . ";'" . $Bnf->banco . ";'" . $Bnf->numero_cuenta . 
        ";" . $segmentoincial  . $asignacion . ';' . $deduccion . ';'  . $neto;        
        
        $this->KRecibo->conceptos = $recibo_de_pago;        
        $this->KRecibo->asignaciones = $asignacion;
        $this->KRecibo->deducciones = $deduccion;
        $this->OperarBeneficiarios++;

        //Insert a Postgres
        $base = $Bnf->porcentaje . "|" . $Bnf->componente_id . "|" . $Bnf->grado_codigo . "|" . $Bnf->grado_nombre; 
        $registro = "(" . $sqlID . "," . $Directivas['oid'] . ",'" . $Bnf->cedula . 
        "','" . trim($Bnf->apellidos) . ", " . trim($Bnf->nombres) . "','" . 
        json_encode($this->KRecibo) . "',Now(),'" . $Bnf->banco . "','" . $Bnf->numero_cuenta . 
        "','" . $Bnf->tipo . "','" . $Bnf->situacion . "'," . $Bnf->estatus_activo . 
        ",'SSSIFANB'," . $neto . ", '" . $base . "', '" . $Bnf->grado_nombre . "','','','','TI')";
        

        
    
    }else{
      $log = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres . ';';      
    }

    $this->SSueldoBase += $Bnf->sueldo_base;
    $this->Asignacion += $asignacion;
    $this->Deduccion += $deduccion;
    $this->Neto += $neto;
    $obj["csv"] = $linea;
    $obj["sql"] = $registro;
    $obj["log"] = $log;
    return $obj;

}




  /**
  * Generar Codigos por Patrones en la Red de Inteligencia Pensionados Sobrevivientes
  * FALLECIDOS CON PENSION
  * @param MBeneficiario
  * @param KCalculoLote
  * @param KPerceptron
  * @param KDirectiva
  * @param object
  * @return void
  */
  private function generarConPatronesFCP(MBeneficiario &$Bnf, KCalculoLote &$CalculoLote, KPerceptron &$Perceptron, $fecha, $Directivas, $v, $sqlID){
    $Bnf->cedula = $v->cedula;
    $Bnf->deposito_banco = ""; //$v->cap_banco; //Individual de la Red
    $Bnf->apellidos = $v->apellidos; //Individual del Objeto
    $Bnf->nombres = $v->nombres; //Individual del Objeto
    
    $Bnf->fecha_ingreso = $v->fecha_ingreso;
    $Bnf->fecha_nacimiento = $v->f_nacimiento;
    $Bnf->adultoMayor = $this->Adulto_Mayor($v->f_nacimiento);
    $Bnf->numero_hijos = $v->n_hijos;
    $Bnf->tipo = $v->tipo;
    $Bnf->banco = $v->banco;
    $Bnf->numero_cuenta = $v->numero_cuenta;
    $Bnf->situacion = $v->situacion;
    $Bnf->no_ascenso = $v->st_no_ascenso;
    $Bnf->componente_id = $v->componente_id;
    $Bnf->componente_nombre = $Directivas['com'][$v->componente_id];
    $Bnf->grado_codigo = $v->codigo;
    $Bnf->grado_nombre = $v->gnombre;
    $Bnf->fecha_ultimo_ascenso = $v->f_ult_ascenso;
    $Bnf->fecha_retiro = $v->f_retiro;
    $Bnf->prima_profesionalizacion_mt =  $v->st_profesion;
    $Bnf->estatus_profesion = $v->st_profesion;
    $Bnf->porcentaje = $v->porcentaje;
    

    $Bnf->prima_especial = $v->monto_especial;
    $Bnf->ano_reconocido = $v->anio_reconocido;
    $Bnf->mes_reconocido = $v->mes_reconocido;
    $Bnf->dia_reconocido = $v->dia_reconocido;
    $Bnf->estatus_activo = $v->status_id;
    $asignacion = 0;
    $deduccion = 0;
    $neto = 0;
    $medida_str = "";
    $cajaahorro_str = "";
    $abreviatura = "";
    $linea = '';
    $registro = '';
    $log = '';
    $anomalia = 0;
    $this->CantidadSobreviviente++;

    $cant = count($this->_MapWNomina['Concepto']);
    $map = $this->_MapWNomina['Concepto'];
    $recibo_de_pago = array(); // Contruir el recibo de pago para un JSON
    $recibo_de_pago_aux = array();

    //GENERADOR DE CALCULOS DINAMICOS
    $CalculoLote->Ejecutar();
    $lstAsignacion = array();
    $segmentoincial = '';
    $fcplinea = '';
    $bonos = 0;
     //Aplicar conceptos de Asignación
    for ($i= 0; $i < $cant; $i++){
      $rs = $map[$i]['codigo'];
      if (isset($Bnf->Concepto[$rs])) {
        $monto_aux = $Bnf->Concepto[$rs]['mt'];
        switch ($Bnf->Concepto[$rs]['TIPO']) {
          case 1: //Leer archivos de texto
            $fcplinea .=  $monto_aux . ";";
            if($rs != 'sueldo_mensual'){
              $segmentoincial .=  $monto_aux . ";";
              $bonos += $monto_aux;
              $lstAsignacion[] = array('desc' =>  $rs, 'tipo' => $Bnf->Concepto[$rs]['TIPO'], 'mont' => $monto_aux);            
            }

            break;
          case 2: //Leer archivos de texto

           break;
          case 3: //Leer archivos de texto    
            
            break;
          case 33:
            break;
          case 96:
            break;
          case 97:
            
            $fcplinea .=  $monto_aux . ";";
            $bonos += $monto_aux;
            if($monto_aux > 0) $lstAsignacion[] = array('desc' =>  $rs, 'tipo' => $Bnf->Concepto[$rs]['TIPO'], 'mont' => $monto_aux);
            break;
          case 98:            
            break;
          case 99:            
            break;       
          default:
           break;
        }
      }else{
        $fcplinea .=  "0;";
        $segmentoincial .= "0;";
      }
    }    


    $this->KReciboSobreviviente->primas = $lstAsignacion;
    $this->KReciboSobreviviente->pension = $Bnf->pension;
    $this->KReciboSobreviviente->cedula = $Bnf->cedula;
    $this->KReciboSobreviviente->porcentaje = $Bnf->porcentaje;
    $this->KReciboSobreviviente->titular =  $Bnf->apellidos . " " .  $Bnf->nombres;
    $this->KReciboSobreviviente->grado = $Bnf->grado_nombre;
    $pension_distribuir = $Bnf->pension;

    $segmentoincial = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres . 
            ';' . $Bnf->fecha_ingreso . ';' . $Bnf->fecha_ultimo_ascenso . 
            ';' . $Bnf->fecha_retiro . ';' . $Bnf->componente_nombre . ';' . $Bnf->grado_codigo . 
            ';' . $Bnf->grado_nombre . ';' . $Bnf->tiempo_servicio . ';' . $Bnf->antiguedad_grado . 
            ';' . $Bnf->numero_hijos . ';' . $Bnf->porcentaje . ';' . $fcplinea;

    $asignaciont = 0;
    $deducciont = 0;
    $fondo_cis = 0;
    $fondo_circulo = 0;
    $fondo_adultomayor = 0;
    $netot = 0;
    $linea = "";
    $i = 0;
    $base_pension = ( $Bnf->sueldo_base * $Bnf->porcentaje ) / 100; //La base con el porcentaje
  
    if(isset($this->FCP[$Bnf->cedula])){
      $PS = $this->FCP[$Bnf->cedula];
      foreach ($PS as $clv => $val) {
        $medida_str = "";
        $cajaahorro_str = "";
        $cporc = ( $Bnf->pension * $PS[$i]['porcentaje'] ) / 100; //Obtengo la pension del familiar en porcion %
        $base_porc = ( $base_pension * $PS[$i]['porcentaje'] ) / 100; //Obtengo porcentaje de la base
        $fondo_cis = round ( (  $base_porc * 6.5 ) / 100, 2) ;
        $fondo_circulo = round ( (  $base_porc * 1.5 ) / 100, 2) ;

        if ( $this->Adulto_Mayor($PS[$i]['nacimiento']) ) { //Evaluar el calculo para el Fondo del Adulto Mayor
          $fondo_adultomayor = round ( ( $base_porc * 1 ) / 100, 2 ) ; 
        }
        
        $retencionesG = $fondo_cis + $fondo_circulo + $fondo_adultomayor;
        $Bnf->retencion = $retencionesG;
        $deducciont += $Bnf->retencion;
        $cporcdistri = ( $pension_distribuir * $PS[$i]['porcentaje'] ) / 100; //Obtengo la pension del familiar en porcion %
        
        $this->KReciboSobreviviente->fondo_cis = $fondo_cis;
        $this->KReciboSobreviviente->fondo_circulo = $fondo_circulo;
        $this->KReciboSobreviviente->fondo_adultomayor = $fondo_adultomayor;
        
        $asignacionp = $cporcdistri;
        $deduccionp = $retencionesG; //$fondo_circulo + $fondo_cis
        $neto = $asignacionp - $deduccionp;

        
        
        if( $PS[$i]['estatus'] == 201  ){
          if ($neto > 0 ){            
            
            $recibo_de_pago[] = array(
              'desc' =>  'PENSION SOBREVIVIENTE', 
              'tipo' => 97,
              'mont' => $asignacionp
            );
            $recibo_de_pago[] = array(
              'desc' =>  'COTIZ 6.5% PENSIONES (FONDO CIS)', 
              'tipo' => 0,
              'mont' => $fondo_cis
            );
            $recibo_de_pago[] = array(
              'desc' =>  'COTIZ 1.5% CIRCULO MILITAR', 
              'tipo' => 0,
              'mont' => $fondo_circulo
            );
            if ( $fondo_adultomayor > 0 ) { //Evaluar el calculo para el Fondo del Adulto Mayor
              $recibo_de_pago[] = array(
                'desc' =>  'FONDO 1% ADULTO MAYOR', 
                'tipo' => 0,
                'mont' => $fondo_adultomayor
              );
            }
           
            $clave = $Bnf->cedula . "|" . $PS[$i]['cedula'];
            $retroactivo = $this->recorrerConceptos($map, $Bnf->Concepto, $clave, $recibo_de_pago);
            
            $asignacionp += $retroactivo;
            $neto =  round($asignacionp,2) - $deduccionp;
            $recibo_de_pago[0] = array(
              'desc' =>  'PENSION SOBREVIVIENTE', 
              'tipo' => 97,
              'mont' => $asignacionp
            );

            $linea .= $segmentoincial . $PS[$i]['cedula'] . ';' . $PS[$i]['apellidos'] . ';' . $PS[$i]['nombres'] . 
            ';' . $PS[$i]['nacimiento'] . ';' . $PS[$i]['parentesco'] . ';' . $PS[$i]['tipo'] . ";'" . $PS[$i]['banco'] . 
            ";'" . $PS[$i]['numero'] . ';' . round($pension_distribuir,2) . ';' . round($PS[$i]['porcentaje'],2) . 
            ';' . $retroactivo . ';' . round($asignacionp,2) . ';' . $fondo_cis . ';' . $fondo_circulo . ';'. $fondo_adultomayor . 
            ';' . round($neto,2) . PHP_EOL;

            $asignaciont += $asignacionp;
            $deducciont += $deduccionp;
            $netot +=  $asignacionp - $deduccionp;
            $this->OperarBeneficiarios++;
           

            $coma = "";
            if($this->OperarBeneficiarios > 1){
              $coma = ",";
            }

            $this->KReciboSobreviviente->conceptos = $recibo_de_pago;        
            $this->KReciboSobreviviente->asignaciones = $asignacionp;
            $this->KReciboSobreviviente->deducciones = $deduccionp;

            $base = $Bnf->porcentaje . "|" . $Bnf->componente_id . "|" . $Bnf->grado_codigo . "|" . $Bnf->grado_nombre;
            $registro .= $coma . "(" . $sqlID . "," . $Directivas['oid'] . ",'" . $Bnf->cedula . 
            "','" . trim($PS[$i]['apellidos']) . ", " . trim($PS[$i]['nombres']) . "','" . 
            json_encode($this->KReciboSobreviviente) . "',Now(),'" . $PS[$i]['banco'] . "','" . $PS[$i]['numero'] . 
            "','" . $PS[$i]['tipo'] . "', '" . $Bnf->situacion . "', " . $Bnf->estatus_activo . 
            ", 'SSSIFANB'," . $neto . ", '" . $base . "', '" . $Bnf->grado_nombre . 
            "','" . $PS[$i]['autorizado'] . "','" . strtoupper($PS[$i]['nautorizado']) . 
            "','" . $PS[$i]['cedula'] . "','" . $PS[$i]['parentesco'] . "')";
            


          
          }else{
            $this->SinPagos++;
            $log .= $segmentoincial . 
            ';' . $PS[$i]['cedula'] . ';' . $PS[$i]['apellidos'] . ';' . $PS[$i]['nombres'] . 
            ';' . $PS[$i]['parentesco'] . ';' . $PS[$i]['tipo'] . ";'" . $PS[$i]['banco'] . ";'" . $PS[$i]['numero'] . 
            ';' . round($Bnf->pension,2) . ';' . round($PS[$i]['porcentaje'],2) . ';0;0;0;0;0;Sin monto a cobrar' . PHP_EOL;            
          }
          
        }else{
          
          $log .= $segmentoincial . 
          ';' . $PS[$i]['cedula'] . ';' . $PS[$i]['apellidos'] . ';' . $PS[$i]['nombres'] . 
          ';' . $PS[$i]['parentesco'] . ';' . $PS[$i]['tipo'] . ";'" . $PS[$i]['banco'] . ";'" . $PS[$i]['numero'] . 
          ';' . round($Bnf->pension,2) . ';' . round($PS[$i]['porcentaje'],2) . 
          ";" . round($asignacionp,2) . ';' . round($deduccionp,2) . ';' . round($neto,2) . ';Paralizado (' . $PS[$i]['estatus'] . 
          ')' . PHP_EOL;

          $this->ParalizadosSobrevivientes++;
        }
        $i++;
        
      }
  
      
    }else{
      $log .=   $segmentoincial . ';S/N;S/N;S/N;S/N;S/N;S/N;S/N;0;0;0;0;0;Militar fallecido sin familiares' . PHP_EOL;        
    }
    
    $this->asignarPresupuesto( "FCIS-00001", $fondo_cis , '0', 'COTIZ 6.5% PENSIONES (FONDO CIS)', '40700000000', '', '');
    $this->asignarPresupuesto( "FCIR-00001", $fondo_circulo , '0', 'COTIZ 1.5% CIRCULO MILITAR', '40700000000', '', '');
    $this->asignarPresupuesto( "PENM-00001", $asignaciont , '0', 'PENSION MILITAR', '40700000000', '', '');

    $this->Asignacion += $asignaciont;
    $this->Deduccion += $deducciont;
    $this->Neto += $netot;

    $obj["csv"] = $linea;
    $obj["sql"] = $registro;
    $obj["log"] = $log;
    return $obj;

  }

  

  function Adulto_Mayor( $fecha ){
    list($ano,$mes,$dia) = explode("-",$fecha);
    $fecha_actual = date('Y-m-d');
    list($anoa,$mesa,$diaa) = explode("-",$fecha_actual);

    $diax = $diaa - $dia;

    if ($diax <= 0 ) {
      $diax = ($diaa + 30) - $dia;
    }

    $mesx = $mesa - $mes;

    if ( $mesx <= 0 ) {
      $mesx = ($mesa + 12) - $mes;
    }

    $edad = $anoa - $ano;
    //print_r($edad);
    if ($edad > 59 ) {
      return false;
    }
    return true;
  }




  /**
  * Generar Codigos por Patrones en la Red de Inteligencia Pensionados Sobrevivientes
  * FALLECIDOS CON PENSION AGUINALDOS 
  * @param MBeneficiario
  * @param KCalculoLote
  * @param KPerceptron
  * @param KDirectiva
  * @param object
  * @return void
  */
  private function gCPatronesFCPAguinaldos(MBeneficiario &$Bnf, KCalculoLote &$CalculoLote, KPerceptron &$Perceptron, $fecha, $Directivas, $v, $sqlID){
    $Bnf->cedula = $v->cedula;
    $Bnf->deposito_banco = ""; //$v->cap_banco; //Individual de la Red
    $Bnf->apellidos = $v->apellidos; //Individual del Objeto
    $Bnf->nombres = $v->nombres; //Individual del Objeto
    
    $Bnf->fecha_ingreso = $v->fecha_ingreso;
    $Bnf->fecha_nacimiento = $v->f_nacimiento;
    $Bnf->adultoMayor = $this->Adulto_Mayor($v->f_nacimiento);
    $Bnf->numero_hijos = $v->n_hijos;
    $Bnf->tipo = $v->tipo;
    $Bnf->banco = $v->banco;
    $Bnf->numero_cuenta = $v->numero_cuenta;
    $Bnf->situacion = $v->situacion;
    $Bnf->no_ascenso = $v->st_no_ascenso;
    $Bnf->componente_id = $v->componente_id;
    $Bnf->componente_nombre = $Directivas['com'][$v->componente_id];
    $Bnf->grado_codigo = $v->codigo;
    $Bnf->grado_nombre = $v->gnombre;
    $Bnf->fecha_ultimo_ascenso = $v->f_ult_ascenso;
    $Bnf->fecha_retiro = $v->f_retiro;
    $Bnf->prima_profesionalizacion_mt =  $v->st_profesion;
    $Bnf->estatus_profesion = $v->st_profesion;
    $Bnf->porcentaje = $v->porcentaje;
    

    $Bnf->prima_especial = $v->monto_especial;
    $Bnf->ano_reconocido = $v->anio_reconocido;
    $Bnf->mes_reconocido = $v->mes_reconocido;
    $Bnf->dia_reconocido = $v->dia_reconocido;
    $Bnf->estatus_activo = $v->status_id;
    $asignacion = 0;
    $deduccion = 0;
    $neto = 0;
    $medida_str = "";
    $cajaahorro_str = "";
    $abreviatura = "";
    $linea = '';
    $registro = '';
    $log = '';
    $anomalia = 0;
    $this->CantidadSobreviviente++;

    $cant = count($this->_MapWNomina['Concepto']);
    $map = $this->_MapWNomina['Concepto'];
    $recibo_de_pago = array(); // Contruir el recibo de pago para un JSON
    $recibo_de_pago_aux = array();

    $CalculoLote->Ejecutar();
    $lstAsignacion = array();
    $segmentoincial = '';
    $fcplinea = '';
    $bonos = 0;
    // echo "<pre>";
     //Aplicar conceptos de Asignación
    for ($i= 0; $i < $cant; $i++){
      $rs = $map[$i]['codigo'];
      // print_r($Bnf->Concepto[$rs]);
      // echo "<br>";

      if (isset($Bnf->Concepto[$rs])) {
        $monto_aux = $Bnf->Concepto[$rs]['mt'];
        // print_r($Bnf->Concepto[$rs]['TIPO']);

        switch ($Bnf->Concepto[$rs]['TIPO']) {
          case 1: //Leer archivos de texto
            $fcplinea .=  $monto_aux . ";";
            if($rs != 'sueldo_mensual'){
              $segmentoincial .=  $monto_aux . ";";
              $bonos += $monto_aux;
              $lstAsignacion[] = array('desc' =>  $rs, 'tipo' => $Bnf->Concepto[$rs]['TIPO'], 'mont' => $monto_aux);
            }

            break;
          case 2: //Leer archivos de texto
           break;
          case 3: //Leer archivos de texto                
            break;
          case 33:
            break;
          case 96:
            break;
          case 97:
            
            $fcplinea .=  $monto_aux . ";";
            $bonos += $monto_aux;
            if($monto_aux > 0) $lstAsignacion[] = array('desc' =>  $rs, 'tipo' => $Bnf->Concepto[$rs]['TIPO'], 'mont' => $monto_aux);
            break;
          case 98:            
            break;
          case 99:            
            break;       
          default:
           break;
        }
      }else{
        $fcplinea .=  "0;";
        $segmentoincial .= "0;";
      }
    }    

    $this->KReciboSobreviviente->porcentaje = $Bnf->porcentaje;
    $this->KReciboSobreviviente->aguinaldos = $bonos;
    $this->KReciboSobreviviente->cedula = $Bnf->cedula;
    $this->KReciboSobreviviente->titular =  $Bnf->apellidos . " " .  $Bnf->nombres;
    $this->KReciboSobreviviente->grado = $Bnf->grado_nombre;
    $aguinaldos_distribuir = $bonos;

    $segmentoincial = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres . 
            ';' . $Bnf->fecha_ingreso . ';' . $Bnf->fecha_ultimo_ascenso . 
            ';' . $Bnf->fecha_retiro . ';' . $Bnf->componente_nombre . ';' . $Bnf->grado_codigo . 
            ';' . $Bnf->grado_nombre . ';' . $Bnf->tiempo_servicio . ';' . $Bnf->antiguedad_grado . 
            ';' . $Bnf->numero_hijos . ';' . $Bnf->porcentaje . ';' . $fcplinea;
            //. round($pension_distribuir,2)
    $asignaciont = 0;
    $deducciont = 0;
    $netot = 0;
    $linea = "";
    $i = 0;
    $base_pension = $bonos; //La base con el porcentaje
  
    if(isset($this->FCP[$Bnf->cedula])){
      $PS = $this->FCP[$Bnf->cedula];
      foreach ($PS as $clv => $val) {
        $medida_str = "";
        $cajaahorro_str = "";
        //$cporc = ( $Bnf->pension * $PS[$i]['porcentaje'] ) / 100; //Obtengo la pension del familiar en porcion %
        //$base_porc = ( $base_pension * $PS[$i]['porcentaje'] ) / 100; //Obtengo porcentaje de la base
        $fondo_cis = 0 ;
        $Bnf->retencion = round( $fondo_cis, 2 );
        $deducciont += $Bnf->retencion;
        $cporcdistri = ( $base_pension * $PS[$i]['porcentaje'] ) / 100; //Obtengo la pension del familiar en porcion %
        
        $this->KReciboSobreviviente->fondo_cis = $Bnf->retencion;
        
        
        $asignacionp = $cporcdistri;
        $deduccionp = $fondo_cis; //round(($asignacionp * 6.5) / 100, 2);
        $neto = $asignacionp - $deduccionp;

        
        
        if( $PS[$i]['estatus'] == 201  ){
          if ($neto > 0 ){            
            
            // $recibo_de_pago[] = array(
            //   'desc' =>  'PENSION SOBREVIVIENTE', 
            //   'tipo' => 97,
            //   'mont' => $asignacionp
            // );
            // $recibo_de_pago[] = array(
            //   'desc' =>  'COTIZ 6.5% PENSIONES (FONDO CIS)', 
            //   'tipo' => 0,
            //   'mont' => $deduccionp
            // );
            $clave = $Bnf->cedula . "|" . $PS[$i]['cedula'];
            $retroactivo = $this->recorrerConceptos($map, $Bnf->Concepto, $clave, $recibo_de_pago);
            
            $asignacionp += $retroactivo;
            $neto =  round($asignacionp,2) - round($deduccionp,2);        
            $recibo_de_pago[0] = array(
              'desc' =>  'AGUINALDOS', 
              'tipo' => 97,
              'mont' => $asignacionp
            );

            $linea .= $segmentoincial . $PS[$i]['cedula'] . ';' . $PS[$i]['apellidos'] . ';' . $PS[$i]['nombres'] . 
            ';' . $PS[$i]['parentesco'] . ';' . $PS[$i]['tipo'] . ";'" . $PS[$i]['banco'] . ";'" . $PS[$i]['numero'] . 
            ';' . round($cporcdistri,2) . ';' . round($PS[$i]['porcentaje'],2) . ';' . $retroactivo . ';' . 
            round($asignacionp,2) . ';' . round($deduccionp,2) . ';' . round($neto,2) . PHP_EOL;

            $asignaciont += $asignacionp;
            $deducciont += $deduccionp;
            $netot +=  $asignacionp - $deduccionp;
            $this->OperarBeneficiarios++;
           

            $coma = "";
            if($this->OperarBeneficiarios > 1){
              $coma = ",";
            }

            $this->KReciboSobreviviente->conceptos = $recibo_de_pago;        
            $this->KReciboSobreviviente->asignaciones = $asignacionp;
            $this->KReciboSobreviviente->deducciones = $deduccionp;

            $base = $Bnf->porcentaje . "|" . $Bnf->componente_id . "|" . $Bnf->grado_codigo . "|" . $Bnf->grado_nombre;
            $registro .= $coma . "(" . $sqlID . "," . $Directivas['oid'] . ",'" . $Bnf->cedula . 
            "','" . trim($PS[$i]['apellidos']) . ", " . trim($PS[$i]['nombres']) . "','" . 
            json_encode($this->KReciboSobreviviente) . "',Now(),'" . $PS[$i]['banco'] . "','" . $PS[$i]['numero'] . 
            "','" . $PS[$i]['tipo'] . "', '" . $Bnf->situacion . "', " . $Bnf->estatus_activo . 
            ", 'SSSIFANB'," . $neto . ", '" . $base . "', '" . $Bnf->grado_nombre . 
            "','" . $PS[$i]['autorizado'] . "','" . strtoupper($PS[$i]['nautorizado']) . 
            "','" . $PS[$i]['cedula'] . "','" . $PS[$i]['parentesco'] . "')";
            


          
          }else{
            $this->SinPagos++;
            $log .= $segmentoincial . 
            ';' . $PS[$i]['cedula'] . ';' . $PS[$i]['apellidos'] . ';' . $PS[$i]['nombres'] . 
            ';' . $PS[$i]['parentesco'] . ';' . $PS[$i]['tipo'] . ";'" . $PS[$i]['banco'] . ";'" . $PS[$i]['numero'] . 
            ';' . round($Bnf->pension,2) . ';' . round($PS[$i]['porcentaje'],2) . ';0;0;0;0;0;Sin monto a cobrar' . PHP_EOL;            
          }
          
        }else{
          
          $log .= $segmentoincial . 
          ';' . $PS[$i]['cedula'] . ';' . $PS[$i]['apellidos'] . ';' . $PS[$i]['nombres'] . 
          ';' . $PS[$i]['parentesco'] . ';' . $PS[$i]['tipo'] . ";'" . $PS[$i]['banco'] . ";'" . $PS[$i]['numero'] . 
          ';' . round($Bnf->pension,2) . ';' . round($PS[$i]['porcentaje'],2) . 
          ";" . round($asignacionp,2) . ';' . round($deduccionp,2) . ';' . round($neto,2) . ';Paralizado (' . $PS[$i]['estatus'] . 
          ')' . PHP_EOL;

          $this->ParalizadosSobrevivientes++;
        }
        $i++;
        
      }
  
      
    }else{
      $log .=   $segmentoincial . ';S/N;S/N;S/N;S/N;S/N;S/N;S/N;0;0;0;0;0;Militar fallecido sin familiares' . PHP_EOL;  
      
      //$this->ParalizadosSobrevivientes++;
    }
    
    $this->asignarPresupuesto( "FCIS-00001", $deducciont , '0', 'FONDO CIS 6.5%', '40700000000', '', '');
    $this->asignarPresupuesto( "PENM-00001", $asignaciont , '0', 'PENSION MILITAR', '40700000000', '', '');

    // }else{ //Recordando calculos

    // }
    //$this->Cantidad += $i;
    //$this->SSueldoBase += $Bnf->pension;
    $this->Asignacion += $asignaciont;
    $this->Deduccion += $deducciont;
    $this->Neto += $netot;

    $obj["csv"] = $linea;
    $obj["sql"] = $registro;
    $obj["log"] = $log;
    return $obj;

  }











  
  /**
   * Permite recorrer nuevamente la lista de los conceptos para la tabla o excel
   * @param map array
   * @param conceptos array
   * @param clave string Cedula del beneficiario con el familiar 1100|00001
   */
  private function recorrerConceptos( $map = array(), $conceptos = array(), $clave = '', &$recibo_de_pago = '') {
    $lst = array();
    $segmentoincial = '';
    $fcplinea = '';
    $monto = 0;
    $asignacion = 0;
    $deduccion = 0;
    $cant = count( $map );
    for ($i= 0; $i < $cant; $i++){
      $rs = $map[$i]['codigo'];

      if (isset($conceptos[$rs])) {
        $monto_aux = $conceptos[$rs]['mt'];        
        switch ($conceptos[$rs]['TIPO']) {
          case 1: //Leer archivos de texto  
            break;
          case 2: //Leer archivos de texto
           break;
          case 3: //Leer archivos de texto                
            break;
          case 33: //Retroactivos
            $monto = 0;
            if ( isset( $this->Retroactivos[$clave][$rs] )){
              $retroactivo = $this->Retroactivos[$clave][$rs];
              $valor = 0;
              try {
                //eval('$valor = ' . $fnx); 
                $fn = $retroactivo['fnxc'];
                eval('$valor = ' . $fn);
              } catch (ParseError $e) {
                  // Report error somehow
              }
              $monto = $valor;
            }
            $segmentoincial =  $monto . ";";
            $asignacion += $monto;
            if($monto != 0) $recibo_de_pago[] = array('desc' =>  $rs, 'tipo' =>$conceptos[$rs]['TIPO'], 'mont' => $monto );
            //asgnar prepuesto
            $this->asignarPresupuesto($rs, $monto, $conceptos[$rs]['TIPO'], $conceptos[$rs]['ABV'], $conceptos[$rs]['part'], $conceptos[$rs]['cuen'], $conceptos[$rs]['codi'] );
            break;
          case 96:
            break;
          case 97:
            break;
          case 98:            
            break;
          case 99:            
            break;       
          default:
           break;
        }
      }else{
        // $fcplinea .=  "0;";
        // $segmentoincial .= "0;";
      }
    }
    return $monto;
  }



  /**
  * DIFERENCIAS DE SUELDO PARA PENSIONADOS SOBREVIVIENTES
  * DIF. FALLECIDOS CON PENSION
  * @param MBeneficiario
  * @param KCalculoLote
  * @param KPerceptron
  * @param KDirectiva
  * @param object
  * @return void
  */
private function generarConPatronesFCPDIF(MBeneficiario &$Bnf, KCalculoLote &$CalculoLote, KPerceptron &$Perceptron, $fecha, $Directivas, $v, $sqlID){
  $Bnf->cedula = $v->cedula;
  $Bnf->deposito_banco = ""; //$v->cap_banco; //Individual de la Red
  $Bnf->apellidos = $v->apellidos; //Individual del Objeto
  $Bnf->nombres = $v->nombres; //Individual del Objeto
  
  $Bnf->fecha_ingreso = $v->fecha_ingreso;
  $Bnf->fecha_nacimiento = $v->f_nacimiento;
  $Bnf->adultoMayor = $this->Adulto_Mayor($v->f_nacimiento);
  $Bnf->numero_hijos = $v->n_hijos;
  $Bnf->tipo = $v->tipo;
  $Bnf->banco = $v->banco;
  $Bnf->numero_cuenta = $v->numero_cuenta;
  $Bnf->situacion = $v->situacion;
  $Bnf->no_ascenso = $v->st_no_ascenso;
  $Bnf->componente_id = $v->componente_id;
  $Bnf->componente_nombre = $Directivas['com'][$v->componente_id];
  $Bnf->grado_codigo = $v->codigo;
  $Bnf->grado_nombre = $v->gnombre;
  $Bnf->fecha_ultimo_ascenso = $v->f_ult_ascenso;
  $Bnf->fecha_retiro = $v->f_retiro;
  $Bnf->prima_profesionalizacion_mt =  $v->st_profesion;
  $Bnf->estatus_profesion = $v->st_profesion;
  $Bnf->porcentaje = $v->porcentaje;
  

  $Bnf->prima_especial = $v->monto_especial;
  $Bnf->ano_reconocido = $v->anio_reconocido;
  $Bnf->mes_reconocido = $v->mes_reconocido;
  $Bnf->dia_reconocido = $v->dia_reconocido;
  $Bnf->estatus_activo = $v->status_id;
  $asignacion = 0;
  $deduccion = 0;
  $neto = 0;
  $medida_str = "";
  $cajaahorro_str = "";
  $abreviatura = "";
  $linea = '';
  $registro = '';
  $log = '';
 
  $cant = count($this->_MapWNomina['Concepto']);
  $map = $this->_MapWNomina['Concepto'];
  
  $recibo_de_pago = array(); // Contruir el recibo de pago para un JSON
  //print_r( $this->Archivos );
  //GENERADOR DE CALCULOS DINAMICOS
  $segmentoincial = '';        
  $i = 0;
  if(isset($this->FCP[$Bnf->cedula])){
    $PS = $this->FCP[$Bnf->cedula];
    
    foreach ($PS as $clv => $val) {
      $asignacion = 0;
      $deduccion = 0;
      $sueldo_mensual = 0;
      $monto_str = '';
      for ($j= 0; $j < $cant; $j++){
        $rs = $map[$j]['codigo'];
        $sCedula =  $Bnf->cedula . $PS[$i]['cedula'] ;
        $monto = $this->obtenerArchivosFCP($sCedula, $rs); 

        if($monto > 0 ){
          $monto_str .= $monto . ';';
          $asignacion += $monto;
          if($monto != 0)$recibo_de_pago[] = array( 'desc' => $map[$j]['nombre'], 'tipo' => 1,'mont' => $monto ); 
          
          $this->asignarPresupuesto( $rs, $asignacion,  '1', $map[$j]['nombre'], $map[$j]['partida'],  '',  $map[$j]['codigo']);  

        }else if($monto < 0) {
          $valor = $monto * -1 ;
          $monto_str .= $valor . ';';
          $deduccion +=  $valor;
          if($valor != 0)$recibo_de_pago[] = array( 'desc' => $map[$j]['nombre'], 'tipo' => 2,'mont' => $valor );
          //$this->asignarPresupuesto( $rs, $deduccion, '2', $map[$i]['nombre'], $map[$i]['partida'], '',  $map[$i]['codigo']);
          $this->asignarPresupuesto( $rs, $deduccion, '2', $map[$j]['nombre'], $map[$j]['partida'],  '',  $map[$j]['codigo']);
        }
      } 

      $segmentoincial = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres . 
      ';' . $Bnf->fecha_ingreso . ';' . $Bnf->fecha_ultimo_ascenso . ';' . $Bnf->fecha_retiro . 
      ';' . $Bnf->componente_nombre . ';' . $Bnf->grado_codigo . ';' . $Bnf->grado_nombre . 
      ';;;' . $Bnf->numero_hijos . ';' . $Bnf->porcentaje ;
      
      //. round($Bnf->total_asignacion,2) . ';' . $Bnf->porcentaje . ';' . $Bnf->sueldo_base . ';' . round($Bnf->pension,2); 
      // . ';' . $monto_str;   
            



     
      $neto = $asignacion - $deduccion;
      
      if ($neto > 0 ){

        $linea .= $segmentoincial . 
            ';' . $PS[$i]['cedula'] . ';' . $PS[$i]['apellidos'] . ';' . $PS[$i]['nombres'] . 
            ';' . $PS[$i]['parentesco'] . ';' . $PS[$i]['tipo'] . ";'" . $PS[$i]['banco'] . ";'" . $PS[$i]['numero'] . 
            ';' . round($Bnf->pension,2) . ';' . round($PS[$i]['porcentaje'],2) . 
            ';;' . round($asignacion,2) . ';' . round($deduccion,2) . ';;' . round($neto,2) . PHP_EOL;
          $this->OperarBeneficiarios++;
          $this->KRecibo->conceptos = $recibo_de_pago;        
          $this->KRecibo->asignaciones = $asignacion;
          $this->KRecibo->deducciones = $deduccion;
          $coma = "";
          if($this->OperarBeneficiarios > 1){
            $coma = ",";
          }

          //Insert a Postgres
          $base = $Bnf->porcentaje . "|" . $Bnf->componente_id . "|" . $Bnf->grado_codigo . "|" . $Bnf->grado_nombre; 
          $registro .= $coma . "(" . $sqlID . "," . $Directivas['oid'] . ",'" . $Bnf->cedula . 
          "','" . trim($PS[$i]['apellidos']) . ", " . trim($PS[$i]['nombres']) . "','" . 
          json_encode($this->KRecibo) . "',Now(),'" .  $PS[$i]['banco']  . "','" . $PS[$i]['numero'] . 
          "','" . $PS[$i]['tipo'] . "','" . $Bnf->situacion . "'," . $Bnf->estatus_activo . 
          ",'SSSIFANB'," . $neto . ", '" . $base . "', '" . $Bnf->grado_nombre . 
          "','" . $PS[$i]['autorizado'] . "','" . strtoupper($PS[$i]['nautorizado']) . 
          "','" . $PS[$i]['cedula'] . "','" . $PS[$i]['parentesco'] . "')";
          $this->Asignacion += $asignacion;
          $this->Deduccion += $deduccion;
          
      }else{
        $log = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres . ';' . PHP_EOL;;
        
      }
      $i++;
  
    }
  }
      
      
      
        //$medida = $this->calcularMedidaJudicial($this->KMedidaJudicial,  $Bnf, $sqlID);
        
       
 
  $this->SSueldoBase += $Bnf->sueldo_base;
  
  $this->Neto += $neto;
  $obj["csv"] = $linea;
  $obj["sql"] = $registro;
  $obj["log"] = $log;
  return $obj;

}





  /**
  * Generar Codigos por Patrones en la Red de Inteligencia Cesta ticket 
  * Retencion Especial
  *
  * @param MBeneficiario
  * @param KCalculoLote
  * @param KPerceptron
  * @param KDirectiva
  * @param object
  * @return void
  */
private function generarConPatronesRetribucionEspecial(MBeneficiario &$Bnf, KCalculoLote &$CalculoLote, KPerceptron &$Perceptron, $fecha, $Directivas, $v, $sqlID){
    $Bnf->cedula = $v->cedula;
    $Bnf->deposito_banco = ""; //$v->cap_banco; //Individual de la Red
    $Bnf->apellidos = $v->apellidos; //Individual del Objeto
    $Bnf->nombres = $v->nombres; //Individual del Objeto
    
    $Bnf->fecha_ingreso = $v->fecha_ingreso;
    $Bnf->fecha_nacimiento = $v->f_nacimiento;
    $Bnf->adultoMayor = $this->Adulto_Mayor($v->f_nacimiento);
    $Bnf->numero_hijos = $v->n_hijos;
    $Bnf->tipo = $v->tipo;
    $Bnf->banco = $v->banco;
    $Bnf->numero_cuenta = $v->numero_cuenta;
    $Bnf->situacion = $v->situacion;
    $Bnf->no_ascenso = $v->st_no_ascenso;
    $Bnf->componente_id = $v->componente_id;
    $Bnf->componente_nombre = $Directivas['com'][$v->componente_id];
    $Bnf->grado_codigo = $v->codigo;
    $Bnf->grado_nombre = $v->gnombre;
    $Bnf->fecha_ultimo_ascenso = $v->f_ult_ascenso;
    $Bnf->fecha_retiro = $v->f_retiro;
    $Bnf->prima_profesionalizacion_mt =  $v->st_profesion;
    $Bnf->estatus_profesion = $v->st_profesion;
    $Bnf->porcentaje = $v->porcentaje;
    

    $Bnf->prima_especial = $v->monto_especial;
    $Bnf->ano_reconocido = $v->anio_reconocido;
    $Bnf->mes_reconocido = $v->mes_reconocido;
    $Bnf->dia_reconocido = $v->dia_reconocido;
    $Bnf->estatus_activo = $v->status_id;
    $asignacion = 0;
    $deduccion = 0;
    $neto = 0;
    $medida_str = "";
    $cajaahorro_str = "";
    $abreviatura = "";
    $linea = '';
    $registro = '';
    $log = '';
    $patron = md5($v->fecha_ingreso.$v->f_retiro.$v->n_hijos.$v->st_no_ascenso.$v->componente_id.
      $v->codigo.$v->f_ult_ascenso.$v->st_profesion.
      $v->anio_reconocido.$v->mes_reconocido.$v->dia_reconocido.$v->porcentaje);

    $cant = count($this->_MapWNomina['Concepto']);
    $map = $this->_MapWNomina['Concepto'];
    $recibo_de_pago = array(); // Contruir el recibo de pago para un JSON
    $segmentoincial = '';  
    //GENERADOR DE CALCULOS DINAMICOS
    if(!isset($Perceptron->Neurona[$patron])){
      $CalculoLote->Ejecutar();
      
      
            
      $medida = $this->calcularMedidaJudicial($this->KMedidaJudicial,  $Bnf, $sqlID, $Directivas);
      $cajaahorro = 0; 
      //Aplicar conceptos de Asignación
      for ($i= 0; $i < $cant; $i++){
        $rs = $map[$i]['codigo'];
        
        if (isset($Bnf->Concepto[$rs])) {
          $concp = $Bnf->Concepto[$rs]; 
          switch ($concp['TIPO']) {
            case 2: //Leer archivos de texto
              $monto_aux = $this->obtenerArchivos($Bnf, $rs);
              $segmentoincial .=  $monto_aux . ";";
              $asignacion += $monto_aux;
              if($monto_aux != 0)$recibo_de_pago[] = array('desc' =>  $rs, 'tipo' => $concp['TIPO'], 'mont' => $monto_aux);
              //asgnar prepuesto
              $this->asignarPresupuesto($rs, $monto_aux, $concp['TIPO'], $concp['ABV'], $concp['part'],$concp['cuen'], $concp['codi']);
              break;
            case 3: //Leer archivos de texto                
              $monto = $this->obtenerArchivos($Bnf, $rs);
              $monto_aux = $monto<0?$monto*-1:$monto;
              $segmentoincial .=  $monto_aux . ";";
              $deduccion += $monto_aux;
              if($monto_aux != 0)$recibo_de_pago[] = array('desc' =>  $rs, 'tipo' => $concp['TIPO'], 'mont' => $monto_aux);
              //asgnar prepuesto
              $this->asignarPresupuesto($rs, $monto_aux, $concp['TIPO'], $concp['ABV'], $concp['part'],$concp['cuen'], $concp['codi']);
              break;
            case 33:
              $monto = 0;
              if ( isset( $this->Retroactivos[$Bnf->cedula][$rs] )){
                $retroactivo = $this->Retroactivos[$Bnf->cedula][$rs];
                $fn = $retroactivo['fnxc'];
                eval('$valor = ' . $fn);
                $monto = $valor;
              }
              $segmentoincial .=  $monto . ";";
              $asignacion += $monto;
              if($monto != 0)$recibo_de_pago[] = array('desc' =>  $rs, 'tipo' => $concp['TIPO'], 'mont' => $monto);
              //asgnar prepuesto
              $this->asignarPresupuesto($rs, $monto, $concp['TIPO'], $concp['ABV'], $concp['part'],$concp['cuen'], $concp['codi']);
              break;
            case 99:
              //$medida_str = $medida[0] . ";";
              $segmentoincial .=  $medida[0] . ";";
              $deduccion +=  $medida[0]; 
              $abreviatura = $concp['ABV'];
              if($medida[0] != 0)$recibo_de_pago[] = array('desc' =>  $medida[1], 'tipo' => 99,'mont' => $medida[0]);
              $this->asignarPresupuesto($rs, $medida[0], '99', $abreviatura, $concp['part'],$concp['cuen'], $concp['codi']); 
              break;
            case 98:
              //$cajaahorro_str = $cajaahorro . ";";
              $segmentoincial .= $cajaahorro . ";";
              $deduccion +=  $cajaahorro;
              $abreviatura = $concp['ABV'];
              if($cajaahorro != 0)$recibo_de_pago[] = array('desc' =>  $abreviatura, 'tipo' => 98,'mont' => $cajaahorro);  
              $this->asignarPresupuesto( $rs, $cajaahorro, '99', $abreviatura, $concp['part'],$concp['cuen'], $concp['codi']);      
              break;
            case 96:
              
              break;
            
            default:
              $monto_aux = $concp['mt'];
              $segmentoincial .=  $monto_aux . ";";
              $asignacion += $concp['TIPO'] == 1? $monto_aux: 0;
              $deduccion += $concp['TIPO'] == 0? $monto_aux: 0;
              //$deduccion += $concp['TIPO'] == 2? $monto_aux: 0;

              if($monto_aux != 0)$recibo_de_pago[] = array('desc' =>  $rs, 'tipo' => $concp['TIPO'], 'mont' => $monto_aux);
              //asgnar prepuesto
              $this->asignarPresupuesto($rs, $concp['mt'], $concp['TIPO'], $concp['ABV'], $concp['part'],$concp['cuen'], $concp['codi']);
              break;
          }
          

        }else{
          $segmentoincial .= "0;";
        }
      }        
      
      
      
      
      $segmentoincial = $Bnf->fecha_ingreso . ';' . $Bnf->fecha_ultimo_ascenso . 
          ';' . $Bnf->fecha_retiro . ';' . $Bnf->componente_nombre . ';' . $Bnf->grado_codigo . 
          ';' . $Bnf->grado_nombre . ';' . $Bnf->tiempo_servicio . ';' . $Bnf->antiguedad_grado . 
          ';' . $Bnf->numero_hijos . ';' . $Bnf->porcentaje . ';' . $segmentoincial;

      $Perceptron->Aprender($patron, array(
        'RECUERDO' => $segmentoincial,
        'ASIGNACION' => $asignacion,
        'DEDUCCION' => $deduccion,
        'SUELDOBASE' => $Bnf->sueldo_base,
        'PENSION' => $Bnf->pension,
        'PORCENTAJE' => $Bnf->porcentaje,
        'CONCEPTO' => $Bnf->Concepto,
        'RECIBO' => $recibo_de_pago
        ) );
             
      $neto = $asignacion - $deduccion;
      
      if ( $neto > 0 ){
        $linea = $Bnf->cedula . ';' . trim($Bnf->apellidos) . ';' . trim($Bnf->nombres) . 
         ';' .  $Bnf->tipo . ";'" . $Bnf->banco . ";'" . $Bnf->numero_cuenta . 
         ";" . $this->generarLinea($segmentoincial) . $medida_str . 
         $cajaahorro_str . $asignacion . ';' . $deduccion . ';'  . $neto;
      
      
      }else{
        $log = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres . ';';
       
      }

      
      $this->KRecibo->conceptos = $recibo_de_pago;        
      $this->KRecibo->asignaciones = $asignacion;
      $this->KRecibo->deducciones = $deduccion;
      //Insert a Postgres
      $base = $Bnf->porcentaje . "|" . $Bnf->componente_id . "|" . $Bnf->grado_codigo . "|" . $Bnf->grado_nombre; 
      $registro = "(" . $sqlID . "," . $Directivas['oid'] . ",'" . $Bnf->cedula . 
      "','" . trim($Bnf->apellidos) . ", " . trim($Bnf->nombres) . "','" . 
      json_encode($this->KRecibo) . "',Now(),'" . $Bnf->banco . "','" . $Bnf->numero_cuenta . 
      "','" . $Bnf->tipo . "','" . $Bnf->situacion . "'," . $Bnf->estatus_activo . 
      ",'SSSIFANB'," . $neto . ", '" . $base . "','" . $Bnf->grado_nombre . "','','','','TI')";

    }else{      //En el caso que exista el recuerdo en la memoria   
      $medida = $this->calcularMedidaJudicial($this->KMedidaJudicial,  $Bnf,  $sqlID, $Directivas);
      $cajaahorro = $this->obtenerCajaAhorro(  $Bnf );

      $deduccion = $Perceptron->Neurona[$patron]["DEDUCCION"];
      $asignacion = $Perceptron->Neurona[$patron]["ASIGNACION"];
      $NConcepto = $Perceptron->Neurona[$patron]["CONCEPTO"];
      
      for ($i= 0; $i < $cant; $i++){
        $result = $map[$i]['codigo'];
        if (isset($NConcepto[$result])) {
          $concp = $NConcepto[$result];

          switch ( $concp['TIPO'] ) {
            case 2: //Leer archivos de texto
              $monto_aux = $this->obtenerArchivos($Bnf, $result);
              $segmentoincial .=  $monto_aux . ";";
              $asignacion += $monto_aux;
              if($monto_aux != 0)$recibo_de_pago[] = array('desc' =>  $result, 'tipo' => $concp['TIPO'], 'mont' => $monto_aux);
              //asgnar prepuesto
              $this->asignarPresupuesto($result, $monto_aux, $concp['TIPO'], $concp['ABV'], $concp['part'],$concp['cuen'], $concp['codi']);
              break;
            case 3: //Leer archivos de texto                
              $monto = $this->obtenerArchivos($Bnf, $result);
              $monto_aux = $monto<0?$monto*-1:$monto;
              $segmentoincial .=  $monto_aux . ";";
              $deduccion += $monto_aux;
              if($monto_aux != 0)$recibo_de_pago[] = array('desc' =>  $result, 'tipo' => $concp['TIPO'], 'mont' => $monto_aux);
              //asgnar prepuesto
              $this->asignarPresupuesto($result, $monto_aux, $concp['TIPO'], $concp['ABV'], $concp['part'],$concp['cuen'], $concp['codi']);
              break;
            case 33:
              $monto = 0;
              if ( isset($this->Retroactivos[$Bnf->cedula][$result])){
                $retroactivo = $this->Retroactivos[$Bnf->cedula][$result];
                $fn = $retroactivo['fnxc'];
                eval('$valor = ' . $fn);
                $monto = $valor;
              }
              $segmentoincial .=  $monto . ";";
              $asignacion += $monto;
              if($monto != 0)$recibo_de_pago[] = array(
                'desc' =>  $result, 
                'tipo' => $concp['TIPO'], 
                'mont' => $monto
              );
              //asgnar prepuesto
              $this->asignarPresupuesto($result, $monto, $concp['TIPO'], $concp['ABV'], $concp['part'],$concp['cuen'], $concp['codi']);
              break;
            case 99:
              //echo "_Imp... MEDIDA \r\n";
              //$medida_str = $medida[0] . ";";
              $segmentoincial .= $medida[0] . ";";
              $deduccion +=  $medida[0];
              $abreviatura = $concp['ABV'];
              if($medida[0] != 0)$recibo_de_pago[] = array('desc' =>  $medida[1], 'tipo' => 99,'mont' => $medida[0]);
              $this->asignarPresupuesto( $result, $medida[0], '99', $abreviatura, $concp['part'],$concp['cuen'], $concp['codi']);
              break;
            
            case 98:
              $segmentoincial .= $cajaahorro . ";";
              //$cajaahorro_str = $cajaahorro . ";";
              $deduccion +=  $cajaahorro;
              $abreviatura = $concp['ABV'];
              if($cajaahorro != 0)$recibo_de_pago[] = array('desc' =>  $abreviatura, 'tipo' => 98,'mont' => $cajaahorro);
              $this->asignarPresupuesto($result, $cajaahorro, '98', $abreviatura, $concp['part'], $concp['part'],$concp['cuen'], $concp['codi']); 
              break;
            
            default:
              $monto_aux = $concp['mt'];
              $abreviatura_aux = $concp['ABV'];
              if($monto_aux != 0)$recibo_de_pago[] = array('desc' =>  $abreviatura_aux, 'tipo' => $concp['TIPO'],'mont' => $monto_aux);
              $this->asignarPresupuesto($result, $monto_aux, $concp['TIPO'], $abreviatura_aux, $concp['part'],$concp['cuen'], $concp['codi']);
              break;            
          } // Fin de Switch           
        } // Fin de si
      } //Fin de repitas

      $neto = $asignacion - $deduccion;
      if( $neto > 0 ){
        $linea = $Bnf->cedula . ';' . trim($Bnf->apellidos) . ';' . trim($Bnf->nombres) . 
        ';' .  $Bnf->tipo . ";'" . $Bnf->banco . ";'" . $Bnf->numero_cuenta . 
        "\";" . $this->generarLineaMemoria($Perceptron->Neurona[$patron]) .
        $medida_str . $cajaahorro_str . $asignacion . ';' . $deduccion . ';' . $neto;
      }else{
        $log = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres . ';';
      
      }
      
      $this->KRecibo->conceptos = $recibo_de_pago;
      $this->KRecibo->asignaciones = $asignacion;
      $this->KRecibo->deducciones = $deduccion;
      //Insert a Postgres
      $base = $Bnf->porcentaje . "|" . $Bnf->componente_id . "|" . $Bnf->grado_codigo . "|" . $Bnf->grado_nombre;
      $registro = "(" . $sqlID . "," . $Directivas['oid'] . ",'" . $Bnf->cedula . 
      "','" . trim($Bnf->apellidos) . ", " . trim($Bnf->nombres) . "','" . 
      json_encode($this->KRecibo) . "',Now(),'" . $Bnf->banco . "','" . $Bnf->numero_cuenta . 
      "','" . $Bnf->tipo . "', '" . $Bnf->situacion . "', " . $Bnf->estatus_activo . 
      ", 'SSSIFANB'," . $neto . ",'" . $base . "','" . $Bnf->grado_nombre . "','','','', 'TI')";
      
    }

    $this->SSueldoBase += $Bnf->sueldo_base;
    $this->Asignacion += $asignacion;
    $this->Deduccion += $deduccion;
    $this->Neto += $neto;
    $obj["csv"] = $linea;
    $obj["sql"] = $registro;
    $obj["log"] = $log;
    return $obj;

}




  /**
  * Generar Codigos por Patrones en la Red de Inteligencia Pensionados Sobrevivientes RetribucionEspecial
  *
  * @param MBeneficiario
  * @param KCalculoLote
  * @param KPerceptron
  * @param KDirectiva
  * @param object
  * @return void
  */
  private function generarConPatronesFCPRetribucionEspecial(MBeneficiario &$Bnf, KCalculoLote &$CalculoLote, KPerceptron &$Perceptron, $fecha, $Directivas, $v, $sqlID){
    $Bnf->cedula = $v->cedula;
    $Bnf->deposito_banco = ""; //$v->cap_banco; //Individual de la Red
    $Bnf->apellidos = $v->apellidos; //Individual del Objeto
    $Bnf->nombres = $v->nombres; //Individual del Objeto
    
    $Bnf->fecha_ingreso = $v->fecha_ingreso;
    $Bnf->fecha_nacimiento = $v->f_nacimiento;
    $Bnf->adultoMayor = $this->Adulto_Mayor($v->f_nacimiento);
    $Bnf->numero_hijos = $v->n_hijos;
    $Bnf->tipo = $v->tipo;
    $Bnf->banco = $v->banco;
    $Bnf->numero_cuenta = $v->numero_cuenta;
    $Bnf->situacion = $v->situacion;
    $Bnf->no_ascenso = $v->st_no_ascenso;
    $Bnf->componente_id = $v->componente_id;
    $Bnf->componente_nombre = $Directivas['com'][$v->componente_id];
    $Bnf->grado_codigo = $v->codigo;
    $Bnf->grado_nombre = $v->gnombre;
    $Bnf->fecha_ultimo_ascenso = $v->f_ult_ascenso;
    $Bnf->fecha_retiro = $v->f_retiro;
    $Bnf->prima_profesionalizacion_mt =  $v->st_profesion;
    $Bnf->estatus_profesion = $v->st_profesion;
    $Bnf->porcentaje = $v->porcentaje;
    

    $Bnf->prima_especial = $v->monto_especial;
    $Bnf->ano_reconocido = $v->anio_reconocido;
    $Bnf->mes_reconocido = $v->mes_reconocido;
    $Bnf->dia_reconocido = $v->dia_reconocido;
    $Bnf->estatus_activo = $v->status_id;
    $asignacion = 0;
    $deduccion = 0;
    $neto = 0;
    $medida_str = "";
    $cajaahorro_str = "";
    $abreviatura = "";
    $linea = '';
    $registro = '';
    $log = '';
    $anomalia = 0;
    $this->CantidadSobreviviente++;

    $cant = count($this->_MapWNomina['Concepto']);
    $map = $this->_MapWNomina['Concepto'];
    $recibo_de_pago = array(); // Contruir el recibo de pago para un JSON
    $recibo_de_pago_aux = array();

    $CalculoLote->Ejecutar();
    $lstAsignacion = array();
    $lstConcepto = array();
    $segmentoincial = '';
    $bonos = 0;
     //Aplicar conceptos de Asignación
    for ($i= 0; $i < $cant; $i++){
      $rs = $map[$i]['codigo'];
      

      if (isset($Bnf->Concepto[$rs])) {
        $lstConcepto = $Bnf->Concepto[$rs];

        switch ($Bnf->Concepto[$rs]['TIPO']) {
          case 1: //Leer archivos de texto
            if($rs != 'sueldo_mensual'){
              $monto_aux = $Bnf->Concepto[$rs]['mt'];
              $segmentoincial .=  $monto_aux . ";";
              $bonos += $monto_aux;
              $lstAsignacion[] = array('desc' =>  $rs, 'tipo' => $Bnf->Concepto[$rs]['TIPO'], 'mont' => $monto_aux);
            }
            break;
          case 2: //Leer archivos de texto
           break;
          case 3: //Leer archivos de texto                
            break;
          case 33:
            break;
          case 96:
            break;
          case 97:
            $monto_aux = $Bnf->Concepto[$rs]['mt'];
            $segmentoincial .=  $monto_aux . ";";
            $bonos += $monto_aux;
            if($monto_aux > 0) $lstAsignacion[] = array('desc' =>  $rs, 'tipo' => $Bnf->Concepto[$rs]['TIPO'], 'mont' => $monto_aux);
            break;
          case 98:            
            break;
          case 99:            
            break;       
          default:
           break;
        }
      }else{
        $segmentoincial .= "0;";
      }
    }    


    //print_r( $lstAsignacion );
    //$Bnf->pension = $asignacion;
    //print_r( $Bnf->pension );
    $this->KReciboSobreviviente->primas = $lstAsignacion;
    $this->KReciboSobreviviente->pension = 0;
    $this->KReciboSobreviviente->cedula = $Bnf->cedula;
    $this->KReciboSobreviviente->porcentaje = $Bnf->porcentaje;
    $this->KReciboSobreviviente->titular =  $Bnf->apellidos . " " .  $Bnf->nombres;
    $this->KReciboSobreviviente->grado = $Bnf->grado_nombre;
    $pension_distribuir = $bonos;

    $segmentoincial = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres . 
    ';' . $Bnf->grado_nombre . ';0;0;0;' . round($pension_distribuir,2) ;
    $asignaciont = 0;
    $deducciont = 0;
    $netot = 0;
    $linea = "";
    $i = 0;
    $base_pension = ( $Bnf->sueldo_base * $Bnf->porcentaje ) / 100; //La base con el porcentaje
  
    if(isset($this->FCP[$Bnf->cedula])){
      $PS = $this->FCP[$Bnf->cedula];
      foreach ($PS as $clv => $val) {
        $medida_str = "";
        $cajaahorro_str = "";
        $cporc = 0; //Obtengo la pension del familiar en porcion %
        $base_porc = 0; //Obtengo porcentaje de la base
        $fondo_cis = 0 ;
        $Bnf->retencion = 0;
        $deducciont = 0;
        $cporcdistri = ( $pension_distribuir * $PS[$i]['porcentaje'] ) / 100; //Obtengo la pension del familiar en porcion %
        
        $this->KReciboSobreviviente->fondo_cis = $Bnf->retencion;
        
        
        $asignacionp = $cporcdistri;
        $deduccionp = 0;
        $neto = $asignacionp - $deduccionp;

        
        
        if( $PS[$i]['estatus'] == 201  ){
          if ($neto > 0 ){            
            $linea .= $segmentoincial . 
            ';' . $PS[$i]['cedula'] . ';' . $PS[$i]['apellidos'] . ';' . $PS[$i]['nombres'] . 
            ';' . $PS[$i]['parentesco'] . ';' . $PS[$i]['tipo'] . ";'" . $PS[$i]['banco'] . ";'" . $PS[$i]['numero'] . 
            ';' . round($pension_distribuir,2) . ';' . round($PS[$i]['porcentaje'],2) . 
            ";" . round($asignacionp,2) . ';' . round($deduccionp,2) . ';' . round($neto,2) . PHP_EOL;

            $asignaciont += $asignacionp;
            $deducciont += $deduccionp;
            $netot +=  $asignacionp - $deduccionp;
            $this->OperarBeneficiarios++;
            $recibo_de_pago[] = array(
              'desc' =>  $lstConcepto['ABV'], 
              'tipo' => $lstConcepto['TIPO'],
              'mont' => $asignacionp
            );

            $coma = "";
            if($this->OperarBeneficiarios > 1){
              $coma = ",";
            }

            $this->KReciboSobreviviente->conceptos = $recibo_de_pago;        
            $this->KReciboSobreviviente->asignaciones = $asignacionp;
            $this->KReciboSobreviviente->deducciones = $deduccionp;

            $base = $Bnf->porcentaje . "|" . $Bnf->componente_id . "|" . $Bnf->grado_codigo . "|" . $Bnf->grado_nombre;
            $registro .= $coma . "(" . $sqlID . "," . $Directivas['oid'] . ",'" . $Bnf->cedula . 
            "','" . trim($PS[$i]['apellidos']) . ", " . trim($PS[$i]['nombres']) . "','" . 
            json_encode($this->KReciboSobreviviente) . "',Now(),'" . $PS[$i]['banco'] . "','" . $PS[$i]['numero'] . 
            "','" . $PS[$i]['tipo'] . "', '" . $Bnf->situacion . "', " . $Bnf->estatus_activo . 
            ", 'SSSIFANB'," . $neto . ", '" . $base . "', '" . $Bnf->grado_nombre . 
            "','" . $PS[$i]['autorizado'] . "','" . strtoupper($PS[$i]['nautorizado']) . 
            "','" . $PS[$i]['cedula'] . "','" . $PS[$i]['parentesco'] . "')";
            

          }else{
            $this->SinPagos++;
            $log .= $segmentoincial . 
            ';' . $PS[$i]['cedula'] . ';' . $PS[$i]['apellidos'] . ';' . $PS[$i]['nombres'] . 
            ';' . $PS[$i]['parentesco'] . ';' . $PS[$i]['tipo'] . ";'" . $PS[$i]['banco'] . ";'" . $PS[$i]['numero'] . 
            ';' . round($Bnf->pension,2) . ';' . round($PS[$i]['porcentaje'],2) . 
            ';0;0;0;Sin monto a cobrar' . PHP_EOL;            
          }
          
        }else{
          
          $log .= $segmentoincial . 
          ';' . $PS[$i]['cedula'] . ';' . $PS[$i]['apellidos'] . ';' . $PS[$i]['nombres'] . 
          ';' . $PS[$i]['parentesco'] . ';' . $PS[$i]['tipo'] . ";'" . $PS[$i]['banco'] . ";'" . $PS[$i]['numero'] . 
          ';' . round($Bnf->pension,2) . ';' . round($PS[$i]['porcentaje'],2) . 
          ";" . round($asignacionp,2) . ';' . round($deduccionp,2) . ';' . round($neto,2) . ';Paralizado (' . $PS[$i]['estatus'] . 
          ')' . PHP_EOL;

          $this->ParalizadosSobrevivientes++;
        }
        $i++;
        
      }
  
      
    }else{
      $log .=  $Bnf->cedula . ";Militar fallecido sin familiares " . PHP_EOL;
      //$this->ParalizadosSobrevivientes++;
    }
    
    //$this->asignarPresupuesto( "FCIS-00001", $deducciont , '0', 'FONDO CIS 6.5%', '40700000000');
    $this->asignarPresupuesto( "RE00001", $asignaciont , '0', 'RETRIBUCION ESPECIAL', '40700000000', '', '');

    // }else{ //Recordando calculos

    // }
    //$this->Cantidad += $i;
    //$this->SSueldoBase += $Bnf->pension;
    $this->Asignacion += $asignaciont;
    $this->Deduccion += $deducciont;
    $this->Neto += $netot;

    $obj["csv"] = $linea;
    $obj["sql"] = $registro;
    $obj["log"] = $log;
    return $obj;

  }




























  //MEDIDA JUDICIAL INDIVIDUAL
  private function calcularMedidaJudicial( KMedidaJudicial &$KMedida, MBeneficiario &$Bnf,  $sqlID, $Directiva ){
    $monto = 0;
    $monto_aux = 0;
    $nombre = "";
    $cuenta = "";
    $autorizado = "";
    $cedula = "";
    if(isset($this->MedidaJudicial[$Bnf->cedula])){          
      $MJ = $this->MedidaJudicial[$Bnf->cedula];
      //( cedu, cben, bene, caut, naut, inst, tcue, ncue, pare, crea, usua, esta, mont ) VALUES ";
      $cantMJ = count($MJ);
      
      for($i = 0; $i < $cantMJ; $i++){        
        $this->CantidadMedida++;      
        $monto = $KMedida->Ejecutar($Bnf->pension, 1, $MJ[$i]['fnxm'], $Directiva);
        $monto_aux += $monto;

        $nombre = $MJ[$i]['nomb'];
        $parentesco = $MJ[$i]['pare'];
        $cbenef = $MJ[$i]['cben'];
        $nbenef = $MJ[$i]['bene'];
        
        $cedula = $MJ[$i]['caut'];        
        $autorizado = $MJ[$i]['auto'];
        $instituto = $MJ[$i]['auto'];
        $tipobanco = $MJ[$i]['tcue'];
        $cuenta = $MJ[$i]['ncue'];

        if( $this->CantidadMedida > 1){
          $this->ComaMedida = ",";
        }

        $this->SQLMedidaJudicial .= $this->ComaMedida . "('" . $sqlID . "','" . $Bnf->cedula . "','" .
        $cbenef . "','" . $nbenef . "','" . $cedula . "','" . $autorizado . "','" . $instituto . 
        "','" . $tipobanco . "','" . $cuenta . "','" . $parentesco . 
        "',Now(),'SSSIFAN',1," . $monto . ")";

      }   
      
    }
    return [ $monto_aux, $nombre, $cuenta, $autorizado, $cedula];
  }

  private function obtenerArchivosFCP( $cedula, $concepto  ){
    
    $monto = $this->KArchivos->Ejecutar($cedula, $concepto, $this->Archivos);
    
    return $monto;
  }
  

  private function obtenerRetroactivos( $cedula, $concepto  ){    
    $monto = $this->KArchivos->Ejecutar($cedula, $concepto, $this->Archivos);    
    return $monto;
  }

  
  private function obtenerArchivos( MBeneficiario &$Bnf, $concepto  ){
    //print_r($this->Archivos);
    $monto = $this->KArchivos->Ejecutar($Bnf->cedula, $concepto, $this->Archivos, $this->_MapWNomina);
    return $monto;
  }


  private function obtenerCajaAhorro( MBeneficiario &$Bnf ){
    return $this->KArchivos->Ejecutar($Bnf->cedula, "CA-00001", $this->Archivos);
  }


  private function generarLinea($Recuerdo){
        return $Recuerdo;
  }


  private function generarLineaMemoria($Recuerdo){
    return $Recuerdo['RECUERDO'];

  }


  /**
   * Asignar el presupuesto como resumen prespuestario
   * @return array
   */
  private function asignarPresupuesto($rs, $mt, $tp, $ab, $part, $cuen, $codi){
    if (isset($this->ResumenPresupuestario[$rs])){
      $mt_aux = $this->ResumenPresupuestario[$rs]['mnt'];
      if($mt_aux > 0){
        $this->ResumenPresupuestario[$rs] =  array( 
          'mnt' => $mt_aux + $mt, 
          'tp' => $tp, 
          'abv' => $ab,
          'estr' => '',
          'part' => $part,
          'cuen' => $cuen,
          'codi' => $codi
        );
      }
    }else{
      if($mt > 0){
        $this->ResumenPresupuestario[$rs] = array( 
          'mnt' => $mt, 
          'tp' => $tp, 
          'abv' => $ab,
          'estr' => '',
          'part' => $part,
          'cuen' => $cuen,
          'codi' => $codi
        );
      }
    }
  }
 
  /**
   * Funcion agregada para validar estatus de los militares
   * @param Beneficiario
   * @return boolean
   */
  private function validarEstatusParalizado(MBeneficiario &$Bnf){
    switch ($Bnf->estatus_id) {
      case 201:
        
        break;
      case 202:

        break;
      default:
        
        break;
    }
  }
 
  /**
  * Crear Txt Para los bancos e insertar movimientos
  *
  * @param string
  * @param int
  * @return array
  */
  function CrearInsertPostgresql( $archivo =  '', $oid = 0, $estatus = 0, $llave = '' ){
    //  Habilitar permisos en linux Centos 7
    //  /sbin/restorecon -v /var/www/html/CI-3.1.10/tmp/script.sh
    // getsebool -a | grep httpd  
    // setsebool -P httpd_ssi_exec=1
    // ausearch -c 'psql' --raw | audit2allow -M mi-psql
    // semodule -i mi-psql.pp
    $fecha = Date("Y-m-d");


    $ruta = explode("/", BASEPATH);
    $c = count($ruta)-2;
    $r = '/';
    for ($i=1; $i < $c; $i++) {
      $r .= $ruta[$i] . '/';
    }
    $strR = $r . 'tmp/' . $archivo . '-SQL';
    $comando = 'cd tmp/; time ./load.sh ' . $strR . ' 2>&1';
    exec($comando, $bash);
    $res[] = $bash;



    $strR = $r . 'tmp/' . $archivo . '-MJ';
    $comando = 'cd tmp/; time ./load.sh ' . $strR . ' 2>&1';
    exec($comando, $bash);
    $res[] = $bash;

    $sUpdate = 'UPDATE  space.nomina SET esta=4, llav = \'' . $llave . '\'  WHERE oid=' . $oid . ';';
    $rs = $this->DBSpace->consultar($sUpdate);

    $this->Resultado = array(
      'a' => $archivo,
      'll' => $llave,
      'rs' => $bash
    );
    
    return $this->Resultado;
  }

  /**
   * Actualizando el codigo fuente de pensiones
   */
  function GitAll(){
    $arr['data'] = "";
    $comando = 'git pull origin master 2>&1';
    exec($comando, $bash);    
    $this->Resultado = array(
      'rs' => $bash
    );
    return $this->Resultado;
  }



  public function cargarFamiliaresFCP(){
    $sConsulta = "SELECT bnf.cedula AS cedu, fam.cedula, 
      regexp_replace(fam.nombres, '[^a-zA-Y0-9 ]', '', 'g') as nombres, 
      regexp_replace(fam.apellidos, '[^a-zA-Y0-9 ]', '', 'g') as apellidos, 
      fam.parentesco, fam.autorizado, 
      fam.f_nacimiento, 
      regexp_replace(fam.nombre_autorizado, '[^a-zA-Y0-9 ]', '', 'g') as nombre_autorizado,  
      fam.tipo, fam.banco, fam.numero,
      fam.porcentaje, fam.motivo, fam.estatus
    FROM beneficiario bnf  JOIN familiar fam ON bnf.cedula=fam.titular";
    $obj = $this->DBSpace->consultar($sConsulta);
    foreach($obj->rs as $c => $v ){      
        $this->FCP[$v->cedu][] = array(
          "cedula" => $v->cedula, 
          "nombres" => $v->nombres,
          "apellidos" => $v->apellidos,
          "parentesco" => $v->parentesco,
          "autorizado" => $v->autorizado,
          "nautorizado" => $v->nombre_autorizado,
          "tipo" => $v->tipo,
          "estatus" => $v->estatus,
          "banco" => $v->banco,
          "numero" => $v->numero,
          "nacimiento" => $v->f_nacimiento,
          "porcentaje" => $v->porcentaje,
          "motivo" => $v->motivo
        );
    }
  }

  public function distribuirFamiliares($Bnf){
    $segmentoincial = '';        

  }

  
  //MEDIDA JUDICIAL INDIVIDUAL
  private function calcularMedidaJudicialFamiliar( KMedidaJudicial &$KMedida, $strCedula = "", $sueldo = 0.00  ){
    $monto = 0;
    $nombre = "";
    $cuenta = "";
    $autorizado = "";
    $cedula = "";
    if(isset($this->MedidaJudicial[$strCedula])){          
      $MJ = $this->MedidaJudicial[$strCedula];
      //( cedu, cben, bene, caut, naut, inst, tcue, ncue, pare, crea, usua, esta, mont ) VALUES ";

      $cantMJ = count($MJ);
      for($i = 0; $i < $cantMJ; $i++){
        $monto += $KMedida->Ejecutar($sueldo, 1, $MJ[$i]['fnxm']);
        $nombre = $MJ[$i]['nomb'];
        $parentesco = $MJ[$i]['pare'];
        $cbenef = $MJ[$i]['cben'];
        $nbenef = $MJ[$i]['bene'];
        
        $cedula = $MJ[$i]['caut'];        
        $autorizado = $MJ[$i]['auto'];
        $instituto = $MJ[$i]['auto'];
        $tipobanco = $MJ[$i]['tcue'];
        $cuenta = $MJ[$i]['ncue'];

        $this->SQLMedidaJudicial .= $this->ComaMedida . "('" . $strCedula . "','" .
        $cbenef . "','" . $nbenef . "','" . $cedula . "','" . $autorizado . "','" . $instituto . 
        "','" . $tipobanco . "','" . $cuenta . "','" . $parentesco . 
        "',Now(),'SSSIFAN',1," . $monto . ")";

      }   
      
    }
    return [ $monto, $nombre, $cuenta, $autorizado, $cedula];
  }



/**
   *  Generar archivos para procesos de lotes (Activos)
   *
   *  Creación de tablas para los esquemas space
   *  ---------------------------------------------
   *  INICIANDO PROCESOS POR LOTES
   *  ---------------------------------------------
   *
   * @return  void
   */
  public function IniciarIndividual($arr){
    

    $this->load->model('comun/Dbpace');
    $this->load->model('fisico/MBeneficiario');
    
     
    $sConsulta = "
      SELECT
        bnf.nombres, bnf.apellidos,
        bnf.cedula, fecha_ingreso,f_ult_ascenso, grado.codigo,grado.nombre as gnombre,
        bnf.componente_id, n_hijos, st_no_ascenso, bnf.status_id,
        st_profesion, monto_especial, anio_reconocido, mes_reconocido,dia_reconocido,bnf.status_id as status_id, 
        bnf.porcentaje, f_retiro, bnf.tipo, bnf.banco, bnf.numero_cuenta, bnf.situacion, bnf.f_nacimiento
        FROM
          beneficiario AS bnf
        JOIN
          grado ON bnf.grado_id=grado.codigo AND bnf.componente_id= grado.componente_id
        WHERE 
        bnf.cedula='" . $arr['id'] . "' 
        ";
    $con = $this->DBSpace->consultar($sConsulta);    
    return $this->asignarBeneficiarioIndividual($con->rs, $arr['id']);
  }


 /**
  * Generar Codigos por Patrones en la Red de Inteligencia Pensionados Sobrevivientes
  *
  * @param MBeneficiario
  * @param KCalculoLote
  * @param KPerceptron
  * @param KDirectiva
  * @param object
  * @return void
  */
  public function asignarBeneficiarioIndividual($obj, $id){
    $this->load->model('kernel/KCalculoLote');
    $this->load->model('kernel/KDirectiva');
    $this->load->model('kernel/KNomina');
    $fecha = date('Y-m-d');
    $Directivas = $this->KDirectiva->Cargar($id, $fecha); //Directivas
   
    foreach ($obj as $k => $v) {
      $Bnf = new $this->MBeneficiario;
      $this->KCalculoLote->Instanciar($Bnf, $Directivas);
      $lst = $this->generarCalculoIndividual($Bnf,  $this->KCalculoLote, $fecha, $Directivas, $v);
    }
      
    
    return $Bnf;
  }

 

  /**
  * Generar Codigos por Patrones en la Red de Inteligencia
  *
  * @param MBeneficiario
  * @param KCalculoLote
  * @param KPerceptron
  * @param KDirectiva
  * @param object
  * @return void
  */
  private function generarCalculoIndividual(MBeneficiario &$Bnf, KCalculoLote &$CalculoLote, $fecha, $Directivas, $v){
   

    $Bnf->cedula = $v->cedula;
    $Bnf->deposito_banco = ""; //$v->cap_banco; //Individual de la Red
    $Bnf->apellidos = $v->apellidos; //Individual del Objeto
    $Bnf->nombres = $v->nombres; //Individual del Objeto
    $Bnf->adultoMayor = $this->Adulto_Mayor($v->f_nacimiento);
    
    $Bnf->fecha_ingreso = $v->fecha_ingreso;
    $Bnf->numero_hijos = $v->n_hijos;
    $Bnf->tipo = $v->tipo;
    $Bnf->banco = $v->banco;
    $Bnf->numero_cuenta = $v->numero_cuenta;
    $Bnf->situacion = $v->situacion;
    $Bnf->no_ascenso = $v->st_no_ascenso;
    $Bnf->componente_id = $v->componente_id;
    $Bnf->componente_nombre = $Directivas['com'][$v->componente_id];
    $Bnf->grado_codigo = $v->codigo;
    $Bnf->grado_nombre = $v->gnombre;
    $Bnf->fecha_ultimo_ascenso = $v->f_ult_ascenso;
    $Bnf->fecha_retiro = $v->f_retiro;
    $Bnf->prima_profesionalizacion_mt =  $v->st_profesion;
    $Bnf->estatus_profesion = $v->st_profesion;
    $Bnf->porcentaje = $v->porcentaje;
    

    $Bnf->prima_especial = $v->monto_especial;
    $Bnf->ano_reconocido = $v->anio_reconocido;
    $Bnf->mes_reconocido = $v->mes_reconocido;
    $Bnf->dia_reconocido = $v->dia_reconocido;
    $Bnf->estatus_activo = $v->status_id;
    
    $CalculoLote->Ejecutar();
    return $Bnf;

  }



  /**
  * Generar Codigos por Patrones en la Red de Inteligencia
  *
  * @param MBeneficiario
  * @param KDirectiva
  * @param object
  * @return void
  */
  function CalcularRetroactivo($data){
    $this->load->model('comun/Dbpace');
    $this->load->model('kernel/KDirectiva');
    $this->load->model('fisico/MBeneficiario');
    $this->load->model('kernel/KCalculoLote');
    $arrRetroActivo = array();


    $sConsulta = "SELECT * FROM space.nomina_retroactivo 
    WHERE desd >= '" . $data['inicio'] . "' AND hast <= '" . $data['fin'] . "' ORDER BY oid;";
    
    //echo $sConsulta;
    $con = $this->DBSpace->consultar($sConsulta);

    
    $Bnf = new $this->MBeneficiario;
    $Bnf->cedula = '';
    // $Bnf->apellidos = $data['apellidos']; //Individual del Objeto
    // $Bnf->nombres = $data['nombres']; //Individual del Objeto
    $Bnf->fecha_ingreso = $data['fingreso'];
    $Bnf->numero_hijos = $data['hijos'];
    
    $Bnf->situacion = $data['situacion'];
    $Bnf->estatus_activo = 201;
    $Bnf->componente_id = $data['componente'];
    
    $Bnf->grado_codigo = $data['codigo'];
    $Bnf->fecha_ultimo_ascenso = $data['fascenso'];
    $Bnf->fecha_retiro = $data['fretiro'];
    $Bnf->porcentaje = $data['porcentaje'];
    $obj = $con->rs;
    $quincenal = false;
    foreach ($obj as $k => $v) {

      $Directivas = $this->KDirectiva->Cargar($v->oidd); //Directivas   
      $Bnf->componente_nombre = $Directivas['com'][$data['componente']]; 
      $this->KCalculoLote->Instanciar($Bnf, $Directivas);
      $Bnf->Concepto = array();
      $Concepto = array();
      $this->KCalculoLote->Ejecutar();
      //print_r($Bnf->Concepto);
      
      foreach ($Bnf->Concepto as $cla => $val) { 
        if ( $v->oidd > 64) {
          $factor = 1;
          if ($val['mt'] > 0){
            //0: Mensual. 1: Quincenal. : 2: Semanal.
            if( $v->forma == 1 ){
              $val['mt'] = $val['mt'] / 2;
              $quincenal = true;
            }
            $Concepto[$cla] = $val;   
          }
        }else{
          $factor = 100000;
          if ($val['mt'] > 0){
            //0: Mensual. 1: Quincenal. : 2: Semanal.            
            $val['mt'] = $val['mt'] / $factor;
            $quincenal = true;            
            $Concepto[$cla] = $val;   
          }
        }
        

      }

      //VACACIONES
      if($v->vacac == 1){
        $valor = ($Bnf->pension/$factor) * $v->vacac;
        $Concepto['vacaciones'] = array('mt' => round($valor,2), 
          'ABV' =>  'vacaciones', 'TIPO' => 1,'part' => ''
        );
      }
      $f = explode("-", $Bnf->fecha_retiro);
      
      //AGINALDOS
      if($v->aguin > 0){
        if( $f[1] < 10 ){
          //if ( $v->anio != $f[0] ) {
            $valor = $Bnf->pension * $v->aguin;
            $Concepto['aguinaldos'] = array('mt' => round($valor,2),'ABV' =>  'aguinaldos', 
              'TIPO' => 1,'part' => ''
            );
          //}
        }
      }
      //RETRIBUCION ESPECIAL
      $valor = 0;
      if (  $data['situacion'] != 'PG' ) {
        $valor = $v->respecialcon;
      }
      
      $Concepto['retribucion_especial'] = array('mt' => round($valor,2), 
          'ABV' =>  'retribucion_especial', 'TIPO' => 1,'part' => ''
      );
      $bterr = 0;
      if (  $data['situacion'] != 'PG'  ) {

        $bterr = ( ( ( $Bnf->sueldo_base * $Bnf->porcentaje ) / 100 ) * $v->bterr ) / 100;
        $Concepto['bono_terr'] = array('mt' => $bterr, 'ABV' =>  'bono_terr', 'TIPO' => 1);
        $Concepto['bono_paz'] = array('mt' => round($v->bpaz,2), 'ABV' =>  'bono_paz', 'TIPO' => 1);
        $Concepto['bono_ssan'] = array('mt' => round($v->bssan,2), 'ABV' =>  'bono_ssan', 'TIPO' => 1);
        $Concepto['bono_utra'] = array('mt' => round($v->butra,2), 'ABV' =>  'bono_utra', 'TIPO' => 1);
        $Concepto['bono_espe'] = array('mt' => round($v->bespe,2), 'ABV' =>  'bono_espe', 'TIPO' => 1);
        $Concepto['bono_smed'] = array('mt' => round($v->bsmed,2), 'ABV' =>  'bono_smed', 'TIPO' => 1);
        $Concepto['bono_fanb'] = array('mt' => round($v->bfanb,2), 'ABV' =>  'bono_fanb', 'TIPO' => 1);
      }

      $Concepto['detalle'] = array(
        'mt' => round($valor,2), 
        'ABV' =>  $v->dire  . ' | ' . $v->anio . ' | ' . $v->mes, 
        'mes' =>  $v->mes,
        'TIPO' => 101,
        'part' => ''
      );

      
      $Bnf->Concepto = $Concepto;

      $arrRetroActivo[] = $Bnf->Concepto;     

    }

    


    

    $Bnf->Retroactivo = $arrRetroActivo;
    
    return $Bnf;

  }

  function totalBono(){

  }

  function ImprimirARC($data){
    // $sConsulta = "SELECT * FROM space.z_arc 
    // WHERE ano = '" . $data['anio'] . "' AND cedu = '" . $data['cedula'] . "' ORDER BY mes;";
    $sConsulta = "SELECT cedu,  mes.oid as mes, mont FROM (
      select cedu,  mes, sum(neto) AS mont from space.pagos pg 
      JOIN space.nomina nom ON pg.nomi=nom.oid
      where cedu='" . $data['cedula'] . "' AND desd >= '" . $data['anio'] . "-01-01' AND desd <= '" . $data['anio'] . "-12-31'
      GROUP BY pg.cedu, nom.mes ) AS arc 
    JOIN space.meses mes ON arc.mes=mes.descr 
    ORDER BY mes.oid";
    if ($data['cedulafamiliar'] != ""){
      $sConsulta = "SELECT cedu, cfam, mes.oid as mes, mont FROM (
        SELECT cedu, cfam, mes, sum(neto) AS mont from space.pagos pg 
          JOIN space.nomina nom ON pg.nomi=nom.oid
          where cedu='" . $data['cedula'] . "' 
          AND cfam='" . $data['cedulafamiliar'] . "' 
          AND desd >= '" . $data['anio'] . "-1-01' AND desd <= '" . $data['anio'] . "-12-31'
          GROUP BY pg.cedu, pg.cfam, nom.mes ) AS arc 
        left JOIN space.meses mes ON arc.mes=mes.descr 
        ORDER BY mes.oid;";
    }
    //echo $sConsulta;
    $con = $this->DBSpace->consultar($sConsulta);
    return $con;

  }

  function ImprimirARCFamiliar(){
    /*
     SELECT * FROM (
      select cedu, pg.cfam, mes, sum(neto) from space.pagos pg 
      JOIN space.nomina nom ON pg.nomi=nom.oid
      where cedu='21628966' AND desd>'2019-12-31'
      GROUP BY pg.cedu, pg.cfam, nom.mes ) AS arc 
    JOIN space.meses mes ON arc.mes=mes.descr 
     */

    return "";
  }

}
