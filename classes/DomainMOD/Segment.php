<?php
/**
 * /classes/DomainMOD/Segment.php
 *
 * This file is part of DomainMOD, an open source domain and internet asset manager.
 * Copyright (c) 2010-2017 Greg Chetcuti <greg@chetcuti.com>
 *
 * Project: http://domainmod.org   Author: http://chetcuti.com
 *
 * DomainMOD is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * DomainMOD is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with DomainMOD. If not, see
 * http://www.gnu.org/licenses/.
 *
 */
//@formatter:off
namespace DomainMOD;

class Segment
{

    public function trimLength($input_segment, $max_length)
    {

        if (strlen($input_segment) > $max_length) {

            $output_segment = substr($input_segment, 0, $max_length);
            $pos = strrpos($output_segment, ", ");

            if ($pos === false) {

                return substr($output_segment, 0, $max_length) . "...";

            }

            return substr($output_segment, 0, $pos) . "...";

        } else {

            return $input_segment;

        }

    }

} //@formatter:on
