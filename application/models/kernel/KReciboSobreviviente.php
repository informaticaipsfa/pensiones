<?php

/**
*
*/
class KReciboSobreviviente extends CI_Model{
    var $conceptos = array();
    var $asignaciones = 0.00;
    var $deducciones = 0.00;
    //
    var $titular = ''; //NOMBRE DEL TITULAR DE LA PENSION
    var $cedula = ''; // CEDULA DEL TITULAR DE LA PENSION
    var $grado = '';
    var $asignacion = 0.00;
    var $primas = array();
    var $porcentaje = 0.00;
    var $pension = 0.00;
    var $fondo_cis = 0.00;

    

	function __construct(){
		parent::__construct();
		if(!isset($this->Dbpace)) $this->load->model('comun/Dbpace');
    }

	


}
