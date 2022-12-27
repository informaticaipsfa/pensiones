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

class KRetroactivo extends CI_Model{

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
  public function IniciarIndividual($arr, $archivo, $autor){
    ini_set('memory_limit', '2024M'); //Aumentar el limite de PHP

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
        -- AND bnf.cedula IN (  '20955773 )
       AND bnf.cedula='" . $arr['cedula'] . "' --RCP '4262481' --FCP='15236250' 
        -- grado.codigo NOT IN(8450, 8510, 8500, 8460, 8470, 8480, 5320) 
        ORDER BY grado.codigo
        -- AND grado.codigo IN( 10, 15)
        -- LIMIT 190 OFFSET 10
        ";
    //echo $sConsulta;
    $con = $this->DBSpace->consultar($sConsulta);
    $this->functionRefelxion = "generarConPatrones";
    if($this->_MapWNomina["tipo"] == "FCP"){
      // $this->cargarFamiliaresFCP();
      // if( $this->_MapWNomina["nombre"] == "DIFERENCIA DE SUELDO" ||  $this->_MapWNomina["nombre"] == "DIFERENCIA DE RETRIBUCION ESPECIAL" || $this->_MapWNomina["nombre"] == "DIFERENCIA DE BONO" ){
      //   $this->functionRefelxion = "generarConPatronesFCPDIF";
      // }else if( $this->_MapWNomina["nombre"] == "RETRIBUCION ESPECIAL"  || $this->_MapWNomina["nombre"] == "BONO RECREACIONAL" || $this->_MapWNomina["nombre"] == "PAGO ESPECIAL ( BONO )"){
      //   $this->functionRefelxion = "generarConPatronesFCPRetribucionEspecial";
      // }else if ($this->_MapWNomina["nombre"] == "AGUINALDOS" ) {
      //   $this->functionRefelxion = "gCPatronesFCPAguinaldos";
      // }else{
      //   $this->functionRefelxion = "generarConPatronesFCP";
      // }
      
    }else{
      if( $this->_MapWNomina["nombre"] == "DIFERENCIA DE SUELDO" ||  $this->_MapWNomina["nombre"] == "DIFERENCIA DE RETRIBUCION ESPECIAL" ||$this->_MapWNomina["nombre"] == "DIFERENCIA DE BONO"){
        $this->functionRefelxion = "generarConPatronesRCPDIF";
      }else if( $this->_MapWNomina["nombre"] == "RETRIBUCION ESPECIAL"  || $this->_MapWNomina["nombre"] == "BONO RECREACIONAL" || $this->_MapWNomina["nombre"] == "PAGO ESPECIAL ( BONO )"){
        $this->functionRefelxion = "generarConPatronesRetribucionEspecial";
      }
    }
    // $this->asignarBeneficiario($con->rs, $arr['id'], $arr['fecha'], $archivo, $autor);

    return $this->asignarBeneficiarioIndividual($con->rs, $arr['id'], $arr['fecha'], $archivo, $autor);

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
  public function asignarBeneficiarioIndividual($obj, $id, $fecha, $archivo, $autor){


    $this->load->model('kernel/KCalculoLote');
    $this->load->model('kernel/KDirectiva');
    $this->load->model('kernel/KNomina');
    
    $this->load->model('kernel/KPerceptron'); //Red Perceptron Aprendizaje de patrones
    $this->load->model('kernel/KAsignaciones'); //Red Perceptron Aprendizaje de patrones
    $this->KNomina->Cargar( $this->_MapWNomina );
    $this->Retroactivos = $this->KAsignaciones->Cargar( $this->_MapWNomina );
    $linea = '';
   
    foreach ($obj as $k => $v) {
      $Directivas = $this->KDirectiva->Cargar('', $fecha); //Directivas

      $Bnf = new $this->MBeneficiario;
      $this->KCalculoLote->Instanciar($Bnf, $Directivas);
      $linea = $this->generarCalculoIndividual($Bnf,  $this->KCalculoLote, $this->KPerceptron, $fecha, $Directivas, $v, $this->KNomina->ID);
    }
      
    
    return $linea;
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
  private function generarCalculoIndividual(MBeneficiario &$Bnf, KCalculoLote &$CalculoLote, KPerceptron &$Perceptron, $fecha, $Directivas, $v, $sqlID){
   
    ini_set('memory_limit', '3024M');
    $Bnf->cedula = $v->cedula;
    $Bnf->deposito_banco = ""; //$v->cap_banco; //Individual de la Red
    $Bnf->apellidos = $v->apellidos; //Individual del Objeto
    $Bnf->nombres = $v->nombres; //Individual del Objeto
    
    $Bnf->fecha_ingreso = $v->fecha_ingreso;
    $Bnf->numero_hijos = $v->n_hijos;
    $Bnf->tipo = $v->tipo;
    $Bnf->banco = $v->banco;
    $Bnf->numero_cuenta = $v->numero_cuenta;
    $Bnf->fecha_nacimiento = $v->f_nacimiento;
    //print_r($v->f_nacimiento);
    $Bnf->adultoMayor = $this->Adulto_Mayor($v->f_nacimiento);
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
        ';' . $Bnf->numero_hijos . ';' . $Bnf->porcentaje;

   

    $segmentoincial = $recuerdo . ';' . $segmentoincial;

            
    $neto = $asignacion - $deduccion;
    if( $Bnf->situacion == "PG" ){          
      if($this->_MapWNomina["nombre"] == "AGUINALDOS"){
        $asignacion = round(($Directivas['salario'] * 5.66666666 ) /4 , 2);
        $neto = $asignacion;
        $this->asignarPresupuesto("AGUI0001", $neto, 1, "", "40701010101","", "AGUI0001");
            
      }else{
        $asignacion = $Directivas['salario'];
        $neto = $Directivas['salario'];
      }
      
    }

    //print_r ($this->KRecibo);

    if ($Bnf->sueldo_base > 0 && $Bnf->porcentaje > 0 && $asignacion > 0 ){
      $linea = $Bnf->cedula . ';' . trim($Bnf->apellidos) . ';' . trim($Bnf->nombres) . 
        ';' .  $Bnf->tipo . ";'" . $Bnf->banco . ";'" . $Bnf->numero_cuenta . 
        ";" . $this->generarLinea($segmentoincial) . $medida_str . 
        $cajaahorro_str . $asignacion . ';' . $deduccion . ';'  . $neto;
    
    
    }else{
      //$log = $Bnf->cedula . ';' . $Bnf->apellidos . ';' . $Bnf->nombres . ';';
      
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

 

    $this->SSueldoBase += $Bnf->sueldo_base;
    $this->Asignacion += $asignacion;
    $this->Deduccion += $deduccion;
    $this->Neto += $neto;
    $obj["js"] = json_encode($this->KRecibo);
    $obj["sql"] = $registro;
    return $obj;

  }

  private function generarLinea($Recuerdo){
    return $Recuerdo;
  }

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
 

  private function obtenerArchivos( MBeneficiario &$Bnf, $concepto  ){
    //print_r($this->Archivos);
    $monto = $this->KArchivos->Ejecutar($Bnf->cedula, $concepto, $this->Archivos, $this->_MapWNomina);
    return $monto;
  }

  private function obtenerCajaAhorro( MBeneficiario &$Bnf ){
    return $this->KArchivos->Ejecutar($Bnf->cedula, "CA-00001", $this->Archivos);
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



  private function Adulto_Mayor( $fecha ){
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
}