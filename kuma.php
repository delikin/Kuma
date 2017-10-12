<?php


        //** Nuevo Wrapper para CURL
        //** Adaptable para el Phrame y sus hijos
        //** 10/05/17
        
        
        class Kuma{
            
            private $curl; //** Objeto curl
            private $multi_curl; //** Objeto curl Multiple
            public $h; //** Header HTTP
            private $cookie; //** Cookie
            private $url; //** 
            public $rs; //** Resultado
            private $cucons; //** Constantes de cURL
            private $header_obj; //** Espejo del header 
            private $cookie_dir = "cookies"; //** Directorio de las cookies
            public $campos; //** Campos del POST
            private $pool = Array();
            //**************************************
            private $esJSON = false; //** Contenido es JSON
            private $esAJAX = false; //** Contenido es AJAX
            
            //** Funcion principal que prepara el objeto CURL
            public function prepara(){
                $this->alistarHeader();
                curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3');
                curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->h); 
                curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookie);
                curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookie);
                curl_setopt($this->curl, CURLOPT_AUTOREFERER, true);
                curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false); 
            }
            
            //** GET
            public function get($url=null){
                $this->prepara();
                if($url == null){
                    if(!empty($this->url)){
                        return $this->ejecuta();
                    }else{
                        //** Error
                        throw new \RuntimeException("No se ha escrito URL");
                    }
                }else{
                    $this->url = $url;
                    $this->CURLOPT_URL = $url;
                    return $this->ejecuta();
                }
            }
            
            //** POST
            public function post($url=null){
                $this->prepara();
                
                //** Validamos y preparamos el campo
                if($this->esJSON == true && (is_array($this->campos) || is_object($this->campos))){
                        $this->campos = json_encode($this->campos);
                }else{
                    if($this->esJSON == false && (is_array($this->campos) || is_object($this->campos))){
                        $this->campos = http_build_query($this->campos);
                    }
                }
                
                
                if($url == null){
                    if(!empty($this->url)){
                        $this->CURLOPT_POST = 1;                
                        $this->CURLOPT_POSTFIELDS = $this->campos;                          
                        return $this->ejecuta();
                    }else{
                        //** Error
                        throw new \RuntimeException("No se ha escrito URL");
                    }
                }else{
                //** Verificamos los campos
                $this->CURLOPT_POST = 1;                
                $this->CURLOPT_POSTFIELDS = $this->campos;  
                return $this->ejecuta();
                }
                

                
            }
            
            
            //** Ejecuta
            private function ejecuta(){
                $this->rs = curl_exec($this->curl);
                curl_close($this->curl);
                return $this->rs;
            }
            
            
            
            //** Constructor
            function __construct(){
                $this->curl = curl_init();
                
                //** Header por defecto 
                $header = array();
                $header[0]  = "Accept: text/xml,application/xml,application/xhtml+xml,application/json,";
                $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
                $header[]   = "Cache-Control: max-age=0";
                $header[]   = "Connection: keep-alive";
                $header[]   = "Keep-Alive: 300";
                $header[]   = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
                $header[]   = "Accept-Language: en-us,en;q=0.5";
                $this->h = $header;
                
                //** Verificamos el folder de las cookies
                $this->cookie = $this->ranCookie();
                if(!empty($this->cookie_dir) && strlen($this->cookie_dir) > 0){
                        if(is_dir($this->cookie_dir) && is_writable($this->cookie_dir)){
                            //** Nitido
                            $this->cookie = $this->cookie_dir."/".$this->ranCookie();
                        }else{
                            if(mkdir($this->cookie_dir)){
                                $this->cookie = $this->cookie_dir."/".$this->ranCookie();
                            }
                        }
                }
                
                //** Cargamos las constantes;
                $this->cucons = $this->getConstantes();
                $this->parseHeader();
                
                
                
            }
            
            //** Set y Gets para Ahorrar codigo
            public function __set($propiedad,$valor){
                //** Agrega opciones al curl
                if(is_array($this->cucons)){
                    $k = array_search($propiedad,$this->cucons);
                    if(!$k == false){
                        curl_setopt($this->curl, $k, $valor);
                    }
                }
                
                
                
                //** Propiedades existentes
                if($propiedad == "url"){
                            if(property_exists(get_class($this),"url")){
                                curl_setopt($this->curl, CURLOPT_URL, $valor);
                                $this->url = $valor;
                            }
                }
                
                
                //** esJSON
                if($propiedad == "esJSON"){
                            if(property_exists(get_class($this),"esJSON")){
                                    if($valor == true){
                                        $this->addHeader("Content-Type","application/json");
                                        $this->esJSON = true;
                                    }else{
                                        $this->remHeader("Content-Type");
                                        $this->esJSON = false;
                                    }
                                    
                                }                   
                }
    
                if($propiedad == "esAJAX"){
                if(property_exists(get_class($this),"esAJAX")){
                    if($valor == true){
                        $this->addHeader("X-Requested-With","XMLHttpRequest");
                        $this->esAJAX = true;
                    }else{
                        $this->remHeader("X-Requested-With");
                        $this->esAJAX = false;
                    }
                    
                    }               
                }

                //** Si el nombre de la cookie es modificado, asegurar que este en su carpeta
                if($propiedad == "cookie"){
                        
                                if(!empty($this->cookie_dir) && strlen($this->cookie_dir) > 0){
                                
                                        if(is_dir($this->cookie_dir) && is_writable($this->cookie_dir)){
                                            $this->cookie = $this->cookie_dir."/".$valor;
                                        }
                                }else{
                                    $this->cookie = $valor;
                                }
                }
                
            }
            
            //** Metodos GET de la clase
            public function __get($propiedad){
                if($propiedad == "curl"){
                    if(!empty($this->url)){
                            $this->prepara();
                            return $this->curl;                     
                    }else{
                        throw new Exception("Cada objeto debe tener una URL asignada");
                    }
                    
                    return false;
                }
                
                
                if($propiedad == "cookie"){
                    return $this->cookie;
                }   
                
            }
            
            //** Extrae todas las constantes de cURL - Nos va a servir para los setters
            private function getConstantes(){
                $const = get_defined_constants(true);
                $const = array_flip($const["curl"]);
                return $const;
            }
            
            //** Genera una cookie Aleatoria
            private function ranCookie($prefijo=""){
                $rng = $prefijo.md5(mt_rand(0,mt_getrandmax()).time()).".txt";
                return $rng;
            }
            
            //** Analizamos y verificamos el Header
            private function parseHeader(){
                if(is_array($this->h) && !empty($this->h)){
                    foreach($this->h as $e){
                         $campo = explode(":",$e);
                         if(is_array($campo) && !empty($campo)){
                             $this->header_obj[$campo[0]] = trim($campo[1]);
                         }
                         
                    }
                }
                
            }
            
            //** Agrega un campo al header
            public function addHeader($campo,$valor){
                if(is_array($this->header_obj) && !empty($this->header_obj)){
                    $this->header_obj[$campo] = $valor;
                    return true;
                }
                
                return false;
            }
            
            //** Eliminamos un campo del header
            private function remHeader($campo){
                if(is_array($this->header_obj) && !empty($this->header_obj)){                       
                    if(array_key_exists($campo,$this->header_obj)){
                        unset($this->header_obj[$campo]);
                        return true;
                    }
                    
                    
                }
                
                return false;
            }

            //** Prepara el header modificado
            private function alistarHeader(){
                $this->h = Array();
                
                foreach($this->header_obj as $k=>$v){
                    $this->h[] = "$k: $v";
                }
                
            }
            
            
            //** Verifica si un String es un JSON valido
            public function esJSON($texto=null){
                if(is_string($texto) && strlen($texto) > 2 && !$texto==null){
                    json_decode($texto);
                    return (json_last_error() == JSON_ERROR_NONE);
                }
                
                return false;
            }
            
            //* Funcion que verifica si se puede ejecutar cURL o alguna dependencia
            public function permiso(){
                $lst = get_loaded_extensions();
                if(in_array("curl",$lst)){
                    return true;
                }
                    return false;
            }
            
            
            //**************************************************************************
            //*******************  EJECUCION MULTIPLE  *************** 18/05/17 ********
            //**************************************************************************
            
            //** Crea el Recurso Multi
            private function multi_inicia(){
                $this->multi_curl = curl_multi_init(); 
                if(is_resource($this->multi_curl)){
                    return true;
                }
                
            }
            
            //** Agrega un nuevo objeto al Pool
            public function multi_agrega($curl){

                if(!is_resource($this->multi_curl)){
                    $this->multi_inicia();
                }
                
                //** Verificamos que el parametro sea un recurso
                if(is_resource($curl) && get_resource_type($curl) == "curl"){
                    if(curl_multi_add_handle($this->multi_curl,$curl) == 0){
                        $this->pool[] = $curl;
                        return true;
                    }
                }
            }
            
            //** Ejecuta la cola y devuelve
            public function multi_ejecuta(){
                if(is_resource($this->multi_curl)){
                    
                    $ejec = null;
                            do {
                              curl_multi_exec($this->multi_curl, $ejec);
                              curl_multi_select($this->multi_curl);
                            } while ($ejec > 0);    

                        //** Una vez terminada la ejecucion
                        $resul = Array();
                        foreach($this->pool as $ch){
                            $resul[] = curl_multi_getcontent($ch);
                            curl_multi_remove_handle($this->multi_curl, $ch);
                        }
                        
                        curl_multi_close($this->multi_curl);
                        $this->multi_curl = "";
                        $this->pool = Array();
                        return $resul;
                    
                }
            }
            
            
            
        }
        
        

?>