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

// Ceux-ci seront réutilisés dans les définitions ci-dessous ; c’est pourquoi nous devons d’abord les définir et les fusionner.
$lang = array_merge($lang, array(

	// Version simplifiée, destinée principalement à une utilisation en anglais. 
	// N’autorise pas les lettres avec des signes diacritiques, des traits de soulignement ou des points.
	// Contient au moins 2 caractères, pour les acronymes courants comme « AI », « UE », etc. :
	// 'ACP_RH_TOPICTAGS_REGEX_DEFAULT'				=> '/^[a-z0-9\- ]{2,30}$/i',
	// 'ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'	=> 'lettres de base de l’alphabet latin, 0–9, trait d’union, espace (seront converties en -) ; min. : 2, max. : 30',
	//
	// Version Europe occidentale.
	// Autorise les lettres avec des signes diacritiques.
	// Contient au moins 2 caractères, pour les acronymes courants comme « AI », « UE », etc. :
	'ACP_RH_TOPICTAGS_REGEX_DEFAULT'				=> '/^[\p{Script=Latin}0-9\-_\. ]{2,30}$/u',
	'ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'	=> 'toutes les lettres de l’alphabet latin, 0–9, trait d’union, trait de soulignement, point, espace (seront converties en -) ; min. : 2, max. : 30',
	//
	// Version Eurasie occidentale (autorise les lettres grecques, cyrilliques, et hébraïques).
	// Contient au moins 2 caractères, pour les acronymes courants comme « AI », « UE », etc. :
	// 'ACP_RH_TOPICTAGS_REGEX_DEFAULT'				=> '/^[\p{Script=Latin}\p{Script=Greek}\p{Script=Cyrillic}\p{Script=Hebrew}0-9\-_\. ]{2,30}$/u',
	// 'ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'	=> 'toutes les lettres (latin, grec, cyrillique, hébreu), 0–9, trait d’union, trait de soulignement, point, espace (sera converti en -) ; min. : 2, max. : 30',
	//
	// Version entièrement internationalisée.
	// A un minimum de 1, car dans de nombreux cas, un idéogramme d'Asie de l’Est pour une phrase entière est un seul caractère :
	// 'ACP_RH_TOPICTAGS_REGEX_DEFAULT'				=> '/^[\p{L}0-9\-_\. ]{1,30}$/u',
	// 'ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'	=> 'lettres dans n’importe quelle langue, 0–9, trait d’union, trait de soulignement, point, espace (seront convertis en -) ; min. : 1, max. : 30',
	//
	// Comme vous pouvez le voir, si vous devez activer la prise en charge de
	// quelque chose de spécial, comme des caractères linguistiques ou
	// mathématiques, il vous suffit d’ajouter des blocs Unicode spécifiques
	// ou des codes qui représentent des plages de ces blocs.
	//
	// Si vous avez besoin de caractères particuliers supplémentaires comme @
	// et $, ajoutez-les simplement individuellement. Mais lisez comment
	// écrire des expressions régulières (regex) :
	// https://stackoverflow.com/questions/399078/what-special-characters-must-be-escaped-in-regular-expressions
	// Plusieurs caractères doivent être « échappés » avec / (et il s’agit
	// d’une regex PCRE, elle utilise donc les règles d'échappement PCRE). Un
	// cas courant serait la prise en charge de #, pour les hashtags. Ce
	// caractère n’a pas besoin d’être échappé, il suffit de l’ajouter à la
	// fin de la sélection [...], par exemple : '/^[\p{L}0-9\-_\. #]{1,30}$/u'
	//
	// Vous pouvez également modifier l’un d’entre eux pour qu'il comporte au
	// moins 1 caractère, comme {1,30}, si vous devez tenir compte d’éléments
	// tels que le langage de programmation nommé R.
	//
	// Si vous modifiez le maximum pour ne plus être de 30, effectuez le même
	// changement dans adm/style/topictags_manage_tags.html
));

$lang = array_merge($lang, array(
	// Utilisé sur la page de configuration des paramètres d’un forum
	'ACP_RH_TOPICTAGS_ENABLE'							=> 'Validation des Balises de Sujet RH (RH Topic Tags)',
	'ACP_RH_TOPICTAGS_ENABLE_EXP'							=> 'Validation ou inhibition de la fonctionnalité des balises dans ce forum. L’inhibition du balisage ne supprime pas les balises des sujets du forum - si vous revalidez, elles sont toujours là ; pour une véritable suppression, choisissez l’option « Suppression des balises dans ce forum ».',
	'ACP_FORUM_SETTINGS_RH_TOPICTAGS_PRUNE'					=> 'Suppression des balises dans ce forum',
	'ACP_FORUM_SETTINGS_RH_TOPICTAGS_PRUNE_EXP'				=> 'Cette option <strong>supprime</strong> toutes les balises attribuées aux sujets. NB : pour éviter une suppression accidentelle des balises, il est nécessaire d’inhiber tout d’abord la fonction pour ce forum.',
	'ACP_FORUM_SETTINGS_RH_TOPICTAGS_PRUNE_CONFIRM'			=> 'Cette option <strong>supprime</strong> toutes les balises attribuées aux sujets de ce forum. Il est nécessaire d’inhiber tout d’abord la fonction pour ce forum.',
	'ACP_RH_TOPICTAGS_PRUNING_REQUIRES_TAGGING_DISABLED'	=> 'Pour éviter une suppression accidentelle de balises, vous devez d’abord inhiber la fonction, puis supprimer les balises.',
	'ACP_RH_TOPICTAGS_ERROR'								=> 'Erreur',
	'ACP_RH_TOPICTAGS_UNKNOWN_ERROR'						=> 'Erreur inconnue. Consultez la console javascript pour connaître la réponse du serveur.',

	// configuration
	'TOPICTAGS_INSTALLED'				=> 'Version installée : v%s',

	'ACP_RH_TOPICTAGS_REGEX_EMPTY'		=> 'L’expression régulière ne peut pas être vide à gauche.',
	'ACP_RH_TOPICTAGS_EXP_FOR_USERS_EMPTY'	=> 'L’explication des balises autorisées ne peut pas être vide.',

	'TOPICTAGS_CONFIG'					=> 'Configuration',
	'TOPICTAGS_CONFIG_TAGCLOUD'			=> 'Paramètres du nuage de balises',
	'TOPICTAGS_CONFIG_TAGS'				=> 'Paramètres des balises',
	'TOPICTAGS_MAINTENANCE'				=> 'Maintenance',
	'TOPICTAGS_TITLE'					=> 'RH Balises de sujets',
	'TOPICTAGS_SETTINGS_SAVED'			=> 'La configuration a bien été mise à jour.',
	'TOPICTAGS_WHITELIST_SAVED'			=> 'La liste blanche a bien été mise à jour.',
	'TOPICTAGS_BLACKLIST_SAVED'			=> 'La liste noire a bien été mise à jour.',

	'TOPICTAGS_DISPLAY_TAGCLOUD_ON_INDEX'		=> 'Affichage du nuage de balises sur la page d’index',
	'TOPICTAGS_DISPLAY_TAGCLOUD_ON_INDEX_EXP'	=> 'Si l’option est validée, un nuage de balises est affiché au bas de la page d’index.',

	'TOPICTAGS_DISPLAY_TAGCOUNT_IN_TAGCLOUD'	=> 'Affichage du nombre d’utilisations de chaque balise dans le nuage',
	'TOPICTAGS_DISPLAY_TAGCOUNT_IN_TAGCLOUD_EXP'	=> 'Si l’option est validée, le nuage s’affiche avec une indication du nombre d’utilisations de chaque balise.',

	'TOPICTAGS_MAX_TAGS_IN_TAGCLOUD'			=> 'Nombre maxi de balises dans le nuage',
	'TOPICTAGS_MAX_TAGS_IN_TAGCLOUD_EXP'		=> 'Cela limite, à la valeur configurée, le nombre de balises affichées dans le nuage de balises sur la page d’index.<br />Cela n’a aucun effet sur la « page du nuage de balises » distincte, qui affiche toutes les balises accessibles à l’utilisateur.',

	'TOPICTAGS_DISPLAY_TAGS_IN_VIEWFORUM'		=> 'Affichage des balises dans l’index de chaque forum',
	'TOPICTAGS_DISPLAY_TAGS_IN_VIEWFORUM_EXP'	=> 'Si l’option est valider, les balises attribuées à chaque sujet sont affichées dans l\index de chaque forum.',

	'TOPICTAGS_ENABLE_IN_ALL_FORUMS_ALREADY'	=> 'Le balisage est déjà validé pour tous les forums.',
	'TOPICTAGS_ENABLE_IN_ALL_FORUMS'			=> 'Valider les Balises de sujets dans tous les forums',
	'TOPICTAGS_ENABLE_IN_ALL_FORUMS_EXP'		=> 'Cette option autorise le balisage dans  <em>tous</em> les forums. Il est possible de valider (ou inhiber) la fonctionnalité pour chaque forum individuellement dans Administration des forums.',
	'TOPICTAGS_ENABLE_IN_ALL_FORUMS_DONE'	=> array(
			1 => 'Le balisage a été validé dans %d forum.',
			2 => 'Le balisage a été validé dans %d forums.',
	),

	'TOPICTAGS_DISABLE_IN_ALL_FORUMS_ALREADY'	=> 'Le balisage est déjà inhibé pour tous les forums.',
	'TOPICTAGS_DISABLE_IN_ALL_FORUMS'			=> 'Inhiber le balisage dans tous les forums',
	'TOPICTAGS_DISABLE_IN_ALL_FORUMS_EXP'		=> 'Cette option interdit le balisage dans  <em>tous</em> les forums. Il est possible de valider (ou inhiber) la fonctionnalité pour chaque forum individuellement dans Administration des forums.',
	'TOPICTAGS_DISABLE_IN_ALL_FORUMS_DONE'	=> array(
			1 => 'Le balisage a été inhibé dans %d forum.',
			2 => 'Le balisage a été inhibé dans %d forums.',
	),

	'TOPICTAGS_PRUNE'					=> 'Délestage des balises',
	'TOPICTAGS_PRUNE_EXP'				=> 'Cette option supprime les balises qui ne sont utilisées dans aucun des sujets.',
	'TOPICTAGS_PRUNE_CONFIRM'			=> 'Cette option <strong>supprime</strong> toutes les balises inutilisées.',
	'TOPICTAGS_PRUNE_ASSIGNMENTS_DONE'	=> array(
			0 => '',
			1 => '%d attribution de balise a été supprimée.',
			2 => '%d attributions de balise ont été supprimées.',
	),
	'TOPICTAGS_PRUNE_TAGS_DONE'			=> array(
			0 => 'Il n’y a pas de balise inutilisée à supprimer.',
			1 => '%d balise inutilisée a été supprimée.',
			2 => '%d balises inutilisées ont été supprimées.',
	),

	'TOPICTAGS_PRUNE_FORUMS'			=> 'Suppression des balises dans les forums avec balisage inhibé',
	'TOPICTAGS_PRUNE_FORUMS_EXP'		=> 'Cette option <strong>supprime</strong> toutes les balises qui ont été attribuées à des sujets situés dans des forums où la fonctionnalité a été inhibée.',
	'TOPICTAGS_PRUNE_FORUMS_CONFIRM'	=> 'Voulez-vous <strong>supprimer</strong> toutes les balises dans les sujets situés dans des forums avec balisage inhibé ?',

	'TOPICTAGS_PRUNE_INVALID_TAGS'				=> 'Délestage des balises invalides',
	'TOPICTAGS_PRUNE_INVALID_TAGS_EXP'			=> 'Cela <strong>supprimera</strong> toutes les balises (et leurs affectations aux sujets) qui ne sont plus valides.<br />Cette opération n’est nécessaire que si vous avez modifié l’expression régulière et si vous voulez vous débarrasser de toutes les balises invalides.',
	'TOPICTAGS_PRUNE_INVALID_TAGS_CONFIRM'		=> 'Cela <strong>supprimera</strong> toutes les balises qui ne sont pas conformes à l’expression régulière. Cela peut enlever beaucoup de choses si vous ne faites pas attention !',

	'TOPICTAGS_CALC_COUNT_TAGS'					=> 'Recalculer le nombre de balises',
	'TOPICTAGS_CALC_COUNT_TAGS_EXP'				=> 'Cette option recalcule le nombre des attributions de chaque balise.',
	'TOPICTAGS_CALC_COUNT_TAGS_DONE'			=> 'Les nombres d’attributions ont été recalculés.',

	'TOPICTAGS_ENABLE_WHITELIST'				=> 'Validation de la liste blanche',
	'TOPICTAGS_ENABLE_WHITELIST_EXP'			=> 'Si cette option est validée, seules les balises qui sont conformes à l’expression régulière et qui sont présentes dans la liste blanche seront autorisées.<br /><strong>Note 1</strong> : Si la liste noire est validée et si une balise est présente à la fois dans la liste noire et dans la liste blanche, elle sera refusée.<br /><strong>Note 2</strong> : Pour éviter une perte accidentelle de données, les balises qui sont déjà dans la base mais qui ne sont pas dans la liste blanche ne sont pas supprimées automatiquement et continuent de s’afficher. Vous devez les supprimer manuellement, le cas échéant.',

	'TOPICTAGS_WHITELIST'						=> 'Liste blanche',
	'TOPICTAGS_WHITELIST_EXP'					=> 'Liste des balises autorisées.<br /><strong>Note</strong> : Les balises doivent également être conformes à l’expression régulière. Vérifiez donc qu’elles le sont (pas de contrôle automatique).',

	'TOPICTAGS_ENABLE_BLACKLIST'				=> 'Validation de la liste noire',
	'TOPICTAGS_ENABLE_BLACKLIST_EXP'			=> 'Si cette option est validée, les balises présentes dans la liste noire sont refusées, même si elles sont conformes à l’expression régulière.<br /><strong>Note 1</strong> : Pour éviter une perte accidentelle de données, les balises qui sont déjà dans la base ne sont pas supprimées automatiquement. Vous devez les supprimer manuellement dans chaque sujet.<br /><strong>Note 2</strong> : La liste noire n’est jamais affichée aux utilisateurs.',

	'TOPICTAGS_BLACKLIST'						=> 'Liste noire',
	'TOPICTAGS_BLACKLIST_EXP'					=> 'Liste des balises interdites.<br /><strong>Note</strong> : Les balises qui ne sont pas conformes à l’expression régulière sont toujours refusées.',

	'TOPICTAGS_ALLOWED_TAGS_REGEX'				=> 'Expression régulière des balises autorisées',
	'TOPICTAGS_ALLOWED_TAGS_REGEX_EXP'			=> '<strong>Attention</strong> : Ne changez rien si vous ne savez pas précisément ce que vous faites. <em>Les balises peuvent comporter 30 caractères maximum, et les délimiteurs entre eux utilisés par l’extension à diverses fins sont « / » et « , »,br />de sorte que ces caractères ne doivent pas être ajoutés à l’expression régulière (individuellement ou dans le cadre d’une classe) comme autorisé dans les balises.</em>.<br />Tenez-en compte dans l’écriture de l’expression.<br />Après une modification d’expression régulière, les balises désormais non valides ne peuvent pas faire l’objet d’une recherche, mais sont toujours affichées dans les rubriques.<br />Pour élaguer les balises invalides, consultez « Maintenance ».<br />Expression régulière par défaut : <code>' . $lang['ACP_RH_TOPICTAGS_REGEX_DEFAULT'] . '</code>',

	'TOPICTAGS_CONVERT_SPACE_TO_HYPHEN'			=> 'Conversion des «   » en « - »',
	'TOPICTAGS_CONVERT_SPACE_TO_HYPHEN_EXP'		=> 'Si l’option est validée, les espaces sont automatiquement convertis en traits d’union.<br /><strong>Note 1</strong> : Il faut dans ce cas autoriser « - » dans l’expression régulière, faute de quoi les balises contenant des espaces sont refusées.<br /><strong>Note 2</strong> : Les balises préexistantes qui contiennent des espaces ne sont pas converties automatiquement.',

	'TOPICTAGS_ALLOWED_TAGS_EXP_FOR_USERS'		=> 'Explication destinée aux utilisateurs',
	'TOPICTAGS_ALLOWED_TAGS_EXP_FOR_USERS_EXP'	=> 'Ce texte est affiché aux utilisateurs et devrait expliquer les balises autorisées et les balises interdites.<br />Valeur par défaut : ' . $lang['ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'],

	'TOPICTAGS_MANAGE_TAGS_EXP'					=> 'Ce tableau affiche les balises existantes. Vous pouvez éditer chaque balise ou la supprimer (et avec elle toutes les attributions). Vous pouvez également fusionner des balises en éditant une balise et en lui donnant le même nom qu’une autre - elles sont alors fusionnées automatiquement.',
	'TOPICTAGS_NO_TAGS'							=> 'Aucune balise n’est définie',
	'TOPICTAGS_TAG'								=> 'Balise',
	'TOPICTAGS_ASSIGNMENTS'						=> 'Attributions',
	'TOPICTAGS_NEW_TAG_NAME'					=> 'Nouveau nom',
	'TOPICTAGS_NEW_TAG_NAME_EXP'				=> 'Veuillez saisir le nouveau nom.',
	'TOPICTAGS_TAG_DELETE_CONFIRM'				=> 'Êtes-vous certain de vouloir supprimer la balise <i>%s</i> ? Cela supprime la balise dans <strong>tous les sujets</strong> où elle a été attribuée. <em>L’opération ne peut pas être annulée.</em>',
	'TOPICTAGS_TAG_DELETED'						=> 'La balise a été supprimée.',
	'TOPICTAGS_MISSING_TAG_ID'					=> 'Pas de numéro de balise.',
	'TOPICTAGS_TAG_CHANGED'						=> 'La balise a été modifiée.',
	'TOPICTAGS_TAG_MERGED'						=> "La balise a été fusionnée avec la balise '%s'.",
	'TOPICTAGS_MISSING_TAG_NAMES'				=> 'Pas de nom de balise.',
	'TOPICTAGS_TAG_INVALID'						=> 'La balise "%s" est invalide, vérifiez les paramétrages.',
	'TOPICTAGS_TAG_DOES_NOT_EXIST'				=> 'La balise "%s" n’existe pas.',
	'TOPICTAGS_NO_MODIFICATION'					=> 'La balise n’a pas été modifiée.',

	'TOPICTAGS_SORT_NAME_ASC'					=> 'Noms des balises, A&rarr;Z', // &rarr; est une flèche pointant vers la droite (→)
	'TOPICTAGS_SORT_NAME_DESC'					=> 'Noms des balises, Z&rarr;A',
	'TOPICTAGS_SORT_COUNT_ASC'					=> 'Utilisation des balises, moins&rarr;plus',
	'TOPICTAGS_SORT_COUNT_DESC'					=> 'Utilisation des balises, plus&rarr;moins',
	'TOPICTAGS_SORT_AGE_ASC'					=> 'Âge des balises, ancienne&rarr;récente',
	'TOPICTAGS_SORT_AGE_DESC'					=> 'Âge des balises, récente&rarr;ancienne',

	'TOPICTAGS_AGE_NOTE'						=> 'Remarque : Le tri par âge des balises se fait en réalité par ordre d’apparition dans la base de données, il peut donc ne pas toujours être précis. Si une balise plus ancienne est fusionnée avec une plus récente, elle prend l’ID et l’âge de la plus récente (et vice versa). Un utilitaire d’optimisation de base de données pourrait également perturber l’âge apparent des balises s’il réindexait les balises selon un nouveau critère, par exemple par ordre alphabétique.',
));
