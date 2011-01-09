<?php

/* * ****************************************************************************
 *
 * risParser - parse delay information of DB
 * Copyright (C) 2011 Philipp Waldhauer
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA 02110, USA
 *
 * *************************************************************************** */

require_once('ris.php');

$ris = new RisReader();

$train = $ris->getTrain(TrainType::getByName('IC'), '2371');

?>

<h2>Train: <?php echo $train->name ?></h2>

<ol>
<?php foreach ($train->stations as $station): ?>
        <li <?php if ($station->inTime->isDelayed() || $station->outTime->isDelayed()): ?>style ="color: red;"<?php endif ?>><?php echo $station->name ?>: <?php echo $station->inTime->toString() ?> - <?php echo $station->outTime->toString() ?></li>
    <?php endforeach ?>

</ol>