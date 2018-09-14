<?php
/* Copyright (C) 2014	Maxime Kohlhaas		<support@atm-consulting.fr>
 * Copyright (C) 2014	Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $action
 * $conf
 * $langs
 *
 * $parameters
 * $cols
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

?>
<!-- BEGIN PHP TEMPLATE extrafields_add.tpl.php -->
<?php

print '<div class="center">';
	print '<label class="titlefieldcreate">';
	print 'Male';
	print '</label>';
	print '<input type="text" name="exval" value="">';
	print '&nbsp; ';
	print '<input type="text" name="exval2" value="" >';	// Cancel for create does not post form if we don't know the backtopage
	print '</div>';


?>
<!-- END PHP TEMPLATE extrafields_add.tpl.php -->