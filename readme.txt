=== ic BeSocial ===
Contributors: Jose Cuesta
Tags: social, share, button, facebook, twitter, meneame, bitacoras, delicious, reddit, google buzz, divulgame, divoblogger
Requires at least: 2.9
Tested up to: 3.1.2
Stable tag: 2.0b

== Description ==

Genera botones para el envío o la votación en distintas redes sociales: Facebook, Twitter, Delicious, Reddit, Meneame, Bitacoras.com, Divúlgame y Divoblogger. Opcionalmente puede mostrar contadores con el número de votos o veces que se ha compartido (según la red).

También genera automáticamente las URLs cortas mediante Bit.ly al publicar las entradas (aparecen en una caja de la pantalla de edición para poder consultarlas).

Los botones pueden ser insertados de manera automática (por defecto) o manualmente (mediante la función `ic_BeSocial_Buttons()` en single.php), y funcionan de la siguiente manera:

* **Meneame**: permite enviar la entrada a Meneame o votarla si ya ha sido enviada, pero si la entrada ha sido descartada el botón no aparecerá. El contador muestra el número de votos (meneos).
* **Bitacoras.com**: permite enviar la entrada a Bitacoras.com o votarla si ya ha sido enviada. El contador muestra el número de votos.
* **Reddit**: permite enviar la entrada a Reddit o votarla si ya ha sido enviada. El contador muestra la puntuación.
* **Delicious**: permite guardar el marcador de la entrada en Delicious. El contador muestra el número de veces que ha sido guardado.
* **Google Buzz**: permite compartir la entrada en Google Buzz. El contador muestra el número de veces que ha sido compartido.
* **Facebook**: permite compartir la entrada en Facebook. El contador muestra el número de gente que ha compartido la entrada y el número de gente que le gusta en Facebook.
* **Twitter**: permite retweetear la entrada. El contador muestra el número de retweets.
* **Divúlgame**: permite enviar la entrada a Divúlgame o votarla si ya ha sido enviada, pero si la entrada ha sido descartada el botón no aparecerá. El contador muestra el número de votos.
* **Divoblogger**: permite enviar la entrada a Divoblogger o votarla si ya ha sido enviada, pero si la entrada ha sido descartada el botón no aparecerá. El contador muestra el número de votos.

**Los contadores sólo pueden aparecer en las entradas individuales o en las páginas**. Esto es debido a que todas las APIs utilizadas tienen limitaciones en cuanto al número de consultas que pueden hacerse en un tiempo determinado.

Este plugin ha sido desarrollado por [Inercia Creativa](http://www.inerciacreativa.com/) para el [blog de Maikelnai](http://maikelnai.elcomercio.es/) y liberado para uso y disfrute público.

== Changelog ==

= 2.0a =
* Se incluyen botones para Divúlgame y Divoblogger
* Posibilidad de mostrar los botones en el índice, archivos y páginas del blog

= 1.6 =
* Se incluye botón para Google Buzz

= 1.5 =
* Se incluyen botones para Delicious y Reddit
* Corregido error cuando el título de la entrada lleva comillas
* Corregido error fatal cuando la petición de acortar la URL a Bit.ly falla

= 1.4 =
* Se utiliza Tweetmeme en vez de Bit.ly para contar RT en vez de clicks

= 1.3.4 =
* Traducción al español

= 1.3.3 =
* Posibilidad de ajustar la posición y alineamiento de los botones
* Posibilidad de insertar los botones manualmente mediante `ic_BeSocial_Buttons()` (en la plantilla single.php)

= 1.3.2 =
* Correcciones en las hojas de estilos

= 1.3 =
* Compatibilidad con PHP 4
* Corregido error en el valor por defecto de las opciones

= 1.2 =
* Todos los contadores funcionan por JSONP
* Reescritura del botón de Meneame

= 1.1 =
* Añadido botón de Bitacoras.com

= 1.0 =
* Reescritura total

= 0.2 =
* Añadido soporte para WPTouch

= 0.1 =
* Primera versión en funcionamiento
