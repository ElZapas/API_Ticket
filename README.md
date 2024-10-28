############################
COSAS A TOMAR EN CUENTA
############################

1. Composer: para descargar dependencias y como instalar el paquete para implementar JWT

Ahora, para que es esto? 

Por temas de seguridad se esta usando lo siguiente:

- Almacenar una clave privada ($key) en la API para codificar/decodificar el token.
- Implementar la librería PHP-JWT
- Obtener la variable 'rememberMe' de la solicitud del cliente (React) y asignar una fecha de expiración para el token.
- Codificar el payload con la información del usuario y devolver el token generado por la librería JWT.
- Además del token, enviar la información del usuario
 
Para endpoints protegidos (necesario header Authorization):
- Decodificar con la $key el token recibido por el header
- Obtener del payload el idenficador del usuario y con eso completar las consultas preparadas

2. CODIGO PARA ACEPTAR EL METODO 'PUT' EN PHP: (sera necesario a futuro)
(para el que va a ver lo del API)

 if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
     parse_str(file_get_contents("php://input"),$put_vars);
     echo $put_vars['foo'];
 }


