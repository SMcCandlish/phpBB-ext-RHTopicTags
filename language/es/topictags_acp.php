<?php
/**
*
* @package phpBB Extension - RH Topic Tags
* @copyright © 2014 Robert Heim
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// Estos se reutilizarán en las definiciones siguientes; es por eso que necesitamos definirlos y fusionarlos primero.
$lang = array_merge($lang, array(

	// Versión simplificada, principalmente para uso en idioma inglés.
	// No permite letras con diacríticas, guiones bajos, ni puntos.
	// Tiene un mínimo de 2 caracteres, para acrónimos comunes como "AI", "EU", etc.:
	// 'ACP_RH_TOPICTAGS_REGEX_DEFAULT'				=> '/^[a-z0-9\- ]{2,30}$/i',
	// 'ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'	=> 'letras del alfabeto latino, 0–9, guión, carácter espacial (se convertirá a -); mín.: 2, máx.: 30',
	//
	// Versión de Europa occidental. Permite letras con diacríticas.
	// Tiene un mínimo de 2 caracteres, para acrónimos comunes como "AI", "EU", etc.:
	'ACP_RH_TOPICTAGS_REGEX_DEFAULT'				=> '/^[\p{Script=Latin}0-9\-_\. ]{2,30}$/u',
	'ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'	=> 'letras del alfabeto latino, 0–9, guión, guión bajo, punto, carácter espacial (se convertirá a -); mín.: 2, máx.: 30',
	//
	// Versión de Eurasia occidental (incluye compatibilidad con griego,
	// cirílico, y hebreo)
	// Tiene un mínimo de 2 caracteres, para acrónimos comunes como "AI", "EU", etc.:
	// 'ACP_RH_TOPICTAGS_REGEX_DEFAULT'				=> '/^[\p{Script=Latin}\p{Script=Greek}\p{Script=Cyrillic}\p{Script=Hebrew}0-9\-_\. ]{2,30}$/u',
	// 'ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'	=> 'letras (latinas, griegas, cirílicas, hebreas), 0–9, guión, guión bajo, punto, carácter espacial (se convertirá a -); mín.: 2, máx.: 30',
	//
	// Versión totalmente internacionalizada
	// Tiene un mínimo de 1, porque en muchos casos un ideograma del este de Asia para una frase completa es un solo carácter:
	// 'ACP_RH_TOPICTAGS_REGEX_DEFAULT'				=> '/^[\p{L}0-9\-_\. ]{1,30}$/u',
	// 'ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'	=> 'letras en cualquier idioma, 0–9, guión, guión bajo, punto, carácter espacial (se convertirá a -); mín.: 1, máx.: 30',
	//
	// Como puede ver en esto, si necesita habilitar soporte para algo
	// especial, como caracteres lingüísticos o matemáticos, solo necesita
	// agregar bloques Unicode específicos o códigos que representen rangos
	// de dichos bloques.
	//
	// Si necesita caracteres especiales adicionales, como @ y $, simplemente
	// agréguelos individualmente. Pero lea sobre cómo escribir expresiones
	// regulares (regex):
	// https://stackoverflow.com/questions/399078/what-special-characters-must-be-escaped-in-regular-expressions
	// Varios caracteres deben "escaparse" con \ (y esta es una regex de PCRE,
	// por lo que utiliza las reglas de escape de PCRE). Un caso común sería el
	// de incluir # en los hashtags. No es necesario que este carácter se
	// escape, solo se agrega al final de la selección [...], p. ej.:
	// '/^[\p{L}0-9\-_\. #]{1,30}$/u'
	//
	// También puedes cambiar cualquiera de estos para que tenga un mínimo de
	// 1 carácter, como {1,30}, si necesitas tener en cuenta cosas como el
	// lenguaje de programación llamado R.
	//
	// Si cambia el máximo para que ya no sea 30, realice el mismo cambio en
	// adm/style/topictags_manage_tags.html
));

$lang = array_merge($lang, array(
	// Se utiliza en la página para configurar un foro
	'ACP_RH_TOPICTAGS_ENABLE'								=> 'Habilitar RH Etiquetas de Temas (RH Topic Tags)',
	'ACP_RH_TOPICTAGS_ENABLE_EXP'							=> 'Sea o no habilitado el etiquetado de los temas en este foro. (Al desactivar el etiquetado, las etiquetas no serán eliminadas de los temas en este foro, por lo que cuando se habilite de nuevo, todavía estarán allí, si realmente desea eliminar las etiquetas, utilice la opción “Eliminar las etiquetas de este foro”.)',
	'ACP_FORUM_SETTINGS_RH_TOPICTAGS_PRUNE'					=> 'Eliminar las etiquetas de este foro',
	'ACP_FORUM_SETTINGS_RH_TOPICTAGS_PRUNE_EXP'				=> 'Esto <strong>eliminará</strong> todas las asignaciones de las etiquetas de los temas de este foro. <strong>Nota</strong>: Para evitar el borrado accidental de las etiquetas, es necesario deshabilitar el etiquetado para este foro.',
	'ACP_FORUM_SETTINGS_RH_TOPICTAGS_PRUNE_CONFIRM'			=> 'Esta opción <strong>eliminará</strong> todas las asignaciones de las etiquetas de los temas de este foro, hay que desactivar el etiquetado de este foro para realizar esta acción.',
	'ACP_RH_TOPICTAGS_PRUNING_REQUIRES_TAGGING_DISABLED'	=> 'Para evitar el borrado accidental de las etiquetas, es necesario deshabilitar el etiquetado de este foro para eliminar las asignaciones de etiqueta.',

	// configuración
	'TOPICTAGS_INSTALLED'					=> 'Versión instalada: v%s',

	'ACP_RH_TOPICTAGS_REGEX_EMPTY'			=> 'La expresión regular no se puede dejar vacía.',
	'ACP_RH_TOPICTAGS_EXP_FOR_USERS_EMPTY'	=> 'La explicación de las etiquetas que están permitidas no se puede dejar vacía.',

	'TOPICTAGS_CONFIG'					=> 'Configuración',
	'TOPICTAGS_CONFIG_TAGCLOUD'			=> 'Ajustes de nube de etiquetas',
	'TOPICTAGS_CONFIG_TAGS'				=> 'Ajustes de etiquetas',
	'TOPICTAGS_MAINTENANCE'				=> 'Mantenimiento',
	'TOPICTAGS_TITLE'					=> 'RH Etiquetas de Temas',
	'TOPICTAGS_SETTINGS_SAVED'			=> 'Configuración actualizada correctamente.',
	'TOPICTAGS_WHITELIST_SAVED'			=> 'Lista blanca actualizada correctamente.',
	'TOPICTAGS_BLACKLIST_SAVED'			=> 'Lista negra actualizada correctamente.',

	'TOPICTAGS_DISPLAY_TAGCLOUD_ON_INDEX'		=> 'Mostrar nube de etiquetas en el índice',
	'TOPICTAGS_DISPLAY_TAGCLOUD_ON_INDEX_EXP'	=> 'Cuando se habilita, una nube de etiquetas se muestra en la parte inferior de la página de índice',

	'TOPICTAGS_DISPLAY_TAGCOUNT_IN_TAGCLOUD'		=> 'Mostrar contador de uso de etiquetas en la nube de etiquetas',
	'TOPICTAGS_DISPLAY_TAGCOUNT_IN_TAGCLOUD_EXP'	=> 'Cuando se habilita la nube de etiqueta muestra cuántos temas se marcan con cada etiqueta',

	'TOPICTAGS_MAX_TAGS_IN_TAGCLOUD'			=> 'Max. etiquetas en la nube de etiquetas',
	'TOPICTAGS_MAX_TAGS_IN_TAGCLOUD_EXP'		=> 'Esto limita, al valor configurado, el número de etiquetas que se muestran en la nube de etiquetas en la página de índice.<br />No tiene ningún efecto en la página independiente de la nube de etiquetas, que muestra todas las etiquetas a las que puede acceder el usuario.',

	'TOPICTAGS_DISPLAY_TAGS_IN_VIEWFORUM'		=> 'Mostrar etiquetas viendo un foro',
	'TOPICTAGS_DISPLAY_TAGS_IN_VIEWFORUM_EXP'	=> 'Si se establece en sí, las etiquetas asignadas para cada tema se muestran en la lista de tema.',

	'TOPICTAGS_ENABLE_IN_ALL_FORUMS_ALREADY'	=> 'El etiquetado ya está habilitado para todos los foros.',
	'TOPICTAGS_ENABLE_IN_ALL_FORUMS'			=> 'Habilitar RH Etiquetas de Temas en todos los foros',
	'TOPICTAGS_ENABLE_IN_ALL_FORUMS_EXP'		=> 'Esto permitirá que el etiquetado en <em>todos</em> los foros. Lo puede activar (o desactivar) en un solo foro desde la configuración del foro.',
	'TOPICTAGS_ENABLE_IN_ALL_FORUMS_DONE'	=> array(
			1 => 'El etiquetado se ha habilitado para %d foro.',
			2 => 'El etiquetado se ha habilitado para %d foros.',
	),

	'TOPICTAGS_DISABLE_IN_ALL_FORUMS_ALREADY'	=> 'El etiquetado ya está deshabilitado para todos los foros.',
	'TOPICTAGS_DISABLE_IN_ALL_FORUMS'			=> 'Deshabilitar RH Etiquetas de Temas en todos los foros',
	'TOPICTAGS_DISABLE_IN_ALL_FORUMS_EXP'		=> 'Esto deshabilita el etiquetado en <em>todos</em> los foros. Lo puede activar (o desactivar) en un solo foro desde la configuración del foro.',
	'TOPICTAGS_DISABLE_IN_ALL_FORUMS_DONE'	=> array(
			1 => 'El etiquetado se ha deshabilitado para %d foro.',
			2 => 'El etiquetado se ha deshabilitado para %d foros.',
	),

	'TOPICTAGS_PRUNE'					=> 'Limpiar etiquetas',
	'TOPICTAGS_PRUNE_EXP'				=> 'Esto ELIMINARÁ todas las etiquetas, que no son utilizadas en ningún tema',
	'TOPICTAGS_PRUNE_CONFIRM'			=> 'Esto ELIMINARÁ todas las etiquetas no utilizadas.',
	'TOPICTAGS_PRUNE_ASSIGNMENTS_DONE'	=> array(
			0 => '',
			1 => '%d asignación de etiqueta del tema ha sido eliminada.',
			2 => '%d asignaciones de etiquetas del tema han sido eliminadas.',
	),
	'TOPICTAGS_PRUNE_TAGS_DONE'			=> array(
			0 => 'No hay etiquetas no usadas que podamos eliminar.',
			1 => '%d etiqueta no utilizada ha sido eliminada.',
			2 => '%d etiquetas no utilizadas han sido eliminadas.',
	),

	'TOPICTAGS_PRUNE_FORUMS'			=> 'Limpiar las etiquetas de foros con etiquetado deshabilitado',
	'TOPICTAGS_PRUNE_FORUMS_EXP'		=> 'Esto ELIMINARÁ todas las asignaciones de las etiquetas de los temas que residen en un foro con etiquetado deshabilitado.',
	'TOPICTAGS_PRUNE_FORUMS_CONFIRM'	=> 'Esto ELIMINARÁ todas las etiquetas de todos los hilos que residen en un foro con etiquetado deshabilitado.',

	'TOPICTAGS_PRUNE_INVALID_TAGS'				=> 'Limpiar etiquetas no válidas',
	'TOPICTAGS_PRUNE_INVALID_TAGS_EXP'			=> 'Esto <strong>eliminará</strong> todas las etiquetas (y sus asignaciones a temas) que ya no sean válidas.<br />Esto sólo es necesario si ha cambiado la expresión regular y quiere deshacerse de las etiquetas no válidas.',
	'TOPICTAGS_PRUNE_INVALID_TAGS_CONFIRM'		=> '¡Esto <strong>eliminará</strong> todas las etiquetas que no están conformes con la configuración de expresión regular y puede eliminar una gran cantidad de su material si no tienes cuidado!',

	'TOPICTAGS_CALC_COUNT_TAGS'					=> 'Recalcular el contador de etiquetas',
	'TOPICTAGS_CALC_COUNT_TAGS_EXP'				=> 'Esto volverá a calcular con qué frecuencia se usa cada etiqueta.',
	'TOPICTAGS_CALC_COUNT_TAGS_DONE'			=> 'El contador de etiquetas se ha recalculado.',

	'TOPICTAGS_ENABLE_WHITELIST'				=> 'Habilitar Lista blanca',
	'TOPICTAGS_ENABLE_WHITELIST_EXP'			=> 'Si está habilitado, sólo etiquetas que son conformes con la expresión regular y están presentes en la lista blanca de abajo estarán permitidas.<br /><strong>Nota 1</strong>: Si la lista negra está activada, también, y es una etiqueta en la lista blanca, así como en la lista negra, será rechazada.<br /><strong>Nota 2</strong>: Para evitar la pérdida accidental de datos, las etiquetas que ya están en la base de datos, pero no en la lista blanca no se eliminan automáticamente y se mostrarán también. Debe quitar las etiquetas existentes con a mano.',

	'TOPICTAGS_WHITELIST'						=> 'Lista blanca',
	'TOPICTAGS_WHITELIST_EXP'					=> 'Lista de etiquetas permitidas.<br /><strong>Nota</strong>: Las etiquetas deben ser conformes con la expresión regular, así, así que asegúrese de que todas estas etiquetas se ajustan a sus expresiones regulares (ajustes de abajo, no controladas de forma automática).',

	'TOPICTAGS_ENABLE_BLACKLIST'				=> 'Habilitar Lista negra',
	'TOPICTAGS_ENABLE_BLACKLIST_EXP'			=> 'Si está activado, las variables configuradas en la lista negra serán rechazadas, incluso si están conformes con la expresión regular.<br /><strong>Nota 1</strong>: Para evitar la pérdida accidental de datos, las etiquetas que ya están en la base de datos no se eliminan automáticamente. Debe eliminar a mano de cada tema.<br /><strong>Nota 2</strong>: La lista negra nunca se muestra a los usuarios.',

	'TOPICTAGS_BLACKLIST'						=> 'Lista negra',
	'TOPICTAGS_BLACKLIST_EXP'					=> 'Lista de etiquetas prohibidas.<br /><strong>Nota</strong>: Todas las etiquetas que no están conformes con la expresión regular siempre serán rechazadas.',

	'TOPICTAGS_ALLOWED_TAGS_REGEX'				=> 'Expresión regular para etiquetas permitidas',
	'TOPICTAGS_ALLOWED_TAGS_REGEX_EXP'			=> '<strong>Advertencia</strong>: No cambie esto, si no sabe lo que está haciendo. <em>Las etiquetas pueden tener un máximo de 30 caracteres, y los delimitadores entre ellos utilizados por la extensión para diversos propósitos son "/" y ","<br />por lo que esos caracteres no deben agregarse a la expresión regular (individualmente o como parte de una clase) como se permite en las etiquetas.</em> Por favor considere esto durante el diseño de expresiones regulares.<br />Después de un cambio de expresión regular, las etiquetas que ahora no son válidas no se pueden buscar, pero aún se muestran en los temas. Para podar etiquetas no válidas, consulte "Mantenimiento").<br />Expresión regular predeterminada:  <code>' . $lang['ACP_RH_TOPICTAGS_REGEX_DEFAULT'] . '</code>',

	'TOPICTAGS_CONVERT_SPACE_TO_HYPHEN'			=> 'Convertir “ ” a “-”',
	'TOPICTAGS_CONVERT_SPACE_TO_HYPHEN_EXP'		=> 'Si se establece en sí, todos los espacios (“ ”) se convierten automáticamente a guión (“-”).<br /><strong>Nota 1</strong>: En la expresión regular se debe permitir “-”; de lo contrario se rechazarán las etiquetas con espacios en blanco.<br /><strong>Nota 2</strong>: Las etiquetas existentes con espacios NO se convertirán automáticamente.',

	'TOPICTAGS_ALLOWED_TAGS_EXP_FOR_USERS'		=> 'Explicación para los usuarios',
	'TOPICTAGS_ALLOWED_TAGS_EXP_FOR_USERS_EXP'	=> 'Este texto se muestra a los usuarios y debe explicar qué etiquetas se permiten, y cuáles no.<br />Por defecto: ' . $lang['ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'],

	'TOPICTAGS_MANAGE_TAGS_EXP'					=> 'La tabla muestra todas las etiquetas existentes. Aquí puede eliminarlas (y todas sus asignaciones) o editar una etiqueta. También puede combinar etiquetas, con la edición de una etiqueta y el establecimiento de su nombre para que sea igual a otra etiqueta y así se fusionarán automáticamente.',
	'TOPICTAGS_NO_TAGS'							=> 'No hay etiquetas todavía.',
	'TOPICTAGS_TAG'								=> 'Etiqueta',
	'TOPICTAGS_ASSIGNMENTS'						=> 'Asignaciones',
	'TOPICTAGS_NEW_TAG_NAME'					=> 'Nuevo nombre de etiqueta',
	'TOPICTAGS_NEW_TAG_NAME_EXP'				=> 'Por favor, introduzca un nuevo nombre de etiqueta.',
	'TOPICTAGS_TAG_DELETE_CONFIRM'				=> '¿Seguro que quiere eliminar la etiqueta <i>%s</i>? Esto eliminará la etiqueta de <strong>todos los temas</strong> donde se le asignó. <em>Esto no puede ser revertido.</em>',
	'TOPICTAGS_TAG_DELETED'						=> 'La etiqueta ha sido borrada.',
	'TOPICTAGS_MISSING_TAG_ID'					=> 'Falta el ID de la etiqueta.',
	'TOPICTAGS_TAG_CHANGED'						=> 'La etiqueta ha sido cambiada.',
	'TOPICTAGS_TAG_MERGED'						=> 'La etiqueta se ha fusionado con la etiqueta “%s”.',
	'TOPICTAGS_MISSING_TAG_NAMES'				=> 'Faltan nombres de etiquetas.',
	'TOPICTAGS_TAG_INVALID'						=> 'La etiqueta “%s” no es válida, por favor verifique los ajustes de etiquetas.',
	'TOPICTAGS_TAG_DOES_NOT_EXIST'				=> 'La etiqueta “%s” no existe.',
	'TOPICTAGS_NO_MODIFICATION'					=> 'La etiqueta no fue cambiada.',

	'TOPICTAGS_SORT_NAME_ASC'					=> 'Nombres de etiquetas, A&rarr;Z', // &rarr; es una flecha que apunta hacia la derecha (→)
	'TOPICTAGS_SORT_NAME_DESC'					=> 'Nombres de etiquetas, Z&rarr;A',
	'TOPICTAGS_SORT_COUNT_ASC'					=> 'Uso de etiquetas, menor&rarr;mayor',
	'TOPICTAGS_SORT_COUNT_DESC'					=> 'Uso de etiquetas, mayor&rarr;menor',
	'TOPICTAGS_SORT_AGE_ASC'					=> 'Antigüedad de etiquetas, antigua&rarr;nueva',
	'TOPICTAGS_SORT_AGE_DESC'					=> 'Antigüedad de etiquetas, nueva&rarr;antigua',

	'TOPICTAGS_AGE_NOTE'						=> 'Nota: La clasificación por antigüedad de la etiqueta es realmente por orden de aparición en la base de datos, por lo que puede que no siempre sea precisa. Si una etiqueta antigua se fusiona con una más nueva, adopta el ID y la antigüedad de la más nueva (y viceversa). Una utilidad de optimización de bases de datos también podría alterar la antigüedad aparente de las etiquetas si volviera a indexar las etiquetas según algún criterio nuevo, como alfabéticamente.',
));
