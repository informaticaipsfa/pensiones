<?php
/**
 * IpsfaNet
 * 
 *
 * @package Login
 * @author Carlos Peña
 * @copyright	Derechos Reservados (c) 2014 - 2015, MamonSoft C.A.
 * @link		http://www.mamonsoft.com.ve
 * @since Version 1.0
 *
 */
date_default_timezone_set ( 'America/Caracas' );
define ('__CONTROLADOR', 'Login');

class Login extends MY_Controller {
	
	function __construct(){
		parent::__construct();
		$this->load->helper('url');
		$this->load->library('session');
	}
	
	function index($msj = null) {
		$this->ingresar ();
	}

	/**
	 *Menu
	 */
  	function ingresar() {
		if(isset($_SESSION['usuario'])){
			$this->inicio();	
		}else{
			$this->load->view("login");
		}
		
	}


	protected function inicio(){

		header('Location: ' . base_url() . 'index.php/panel/Panel/index');
	}
	/* 
	| ------------------------------------------------------------
	|	Control de Acciones
	| ------------------------------------------------------------
	*/

	/**
	 * Validar y sincronizar el usuario de conexión
	 *
	 * @access  public
	 * @return mixed
	 */	
	public function validarUsuario(){		
		if(isset($_POST['usuario']) && $_POST['usuario'] != ""){
			
			$this->load->model('usuario/Iniciar');

			$valores["usuario"] = $_POST['usuario'];
			$valores["clave"] = $_POST['clave'];
			$resultado = $this->Iniciar->validarCuenta($valores);			
			if ( $resultado == 1){
				$this->inicio();
			}else{
				echo "Error en el usuario con la base de datos";
			}
		}else{
			$this->salir();
		}
	
	}




	/**
	* Establecer politicas para la recuperacion de clave
	*
	* @access public
	* @return mixed
	*/	
	public function recuperar($msj = ''){
		$data['msj'] = $msj;
		$this->load->view('login/afiliacion/frmRecuperar', $data);	
	}

	/**
	* Registar y asignar tipo al usuario
	*
	* @access public
	* @return mixed
	*/	
	public function registrarUsuario(){
		$this -> load -> model("usuario/usuario","usuario");
		$usuario = new $this -> usuario;
		$usuario->cedula = $_SESSION['cedula'];
		$usuario->tipo = 1;
		$usuario->nombre = $_SESSION['nombreRango'];
		$usuario->sobreNombre = $_POST['usuario'];
		$usuario->correo = $_POST['correo'];
		$usuario->clave = $_POST['clave'];
		$usuario->respuesta = $_SESSION['APIkey'];
		$usuario->perfil = $_SESSION['situacion'];
		if($usuario->existe() == -1){
			$usuario->registrar();
			$this->load->model('comun/Dbipsfa');
			$arr = array(
		      'cedu' => $usuario->cedula,
		      'obse' => 'Creación de Usuario',
		      'fech' => 'now()',
		      'app' => 'Login',
		      'tipo' => 0
			);

    		$this->Dbipsfa->insertarArreglo('traza', $arr);
			$_SESSION['correo'] = $_POST['correo'];
			$_SESSION['estatus'] = 0;
    		$this->enviarCorreoCertificacion();
			$this->load->view('login/afiliacion/frmOk');
		}else{
			$msj = "El usuario se encuentra registrado, intente recuperar la contraseña";
			$this->identificacion($msj);
		}
	}




	/**
	* Permite validar la ultima conexion
	*
	* @access public
	* @return mixed
	*/
	public function ultimaConexion(){
		print_r($_SESSION);
	}

  	public function salir(){
  		session_destroy();
  		$this->load->view ( 'login');
  	}




  	

	function __destruct(){
	}
}