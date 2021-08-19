<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Item_quantity class
 */

class Item_quantity extends Model
{
    public function exists($item_id, $location_id)
    {
        $builder = $this->db->table('item_quantities');
        $builder->where('item_id', $item_id);
        $builder->where('location_id', $location_id);

        return ($builder->get()->getNumRows() == 1);
    }

    public function save($location_detail, $item_id, $location_id)
    {
        if(!$this->exists($item_id, $location_id))
        {
            return $builder->insert('item_quantities', $location_detail);
        }

        $builder->where('item_id', $item_id);
        $builder->where('location_id', $location_id);

        return $builder->update('item_quantities', $location_detail);
    }

    public function get_item_quantity($item_id, $location_id)
    {
        $builder = $this->db->table('item_quantities');
        $builder->where('item_id', $item_id);
        $builder->where('location_id', $location_id);
        $result = $builder->get()->getRow();
        if(empty($result) == TRUE)
        {
            //Get empty base parent object, as $item_id is NOT an item
            $result = new stdClass();

            //Get all the fields from items table (TODO to be reviewed)
            foreach($this->db->list_fields('item_quantities') as $field)
            {
                $result->$field = '';
            }

            $result->quantity = 0;
        }

        return $result;
    }

	/*
	 * changes to quantity of an item according to the given amount.
	 * if $quantity_change is negative, it will be subtracted,
	 * if it is positive, it will be added to the current quantity
	 */
	public function change_quantity($item_id, $location_id, $quantity_change)
	{
		$quantity_old = $this->get_item_quantity($item_id, $location_id);
		$quantity_new = $quantity_old->quantity + $quantity_change;
		$location_detail = array('item_id' => $item_id, 'location_id' => $location_id, 'quantity' => $quantity_new);

		return $this->save($location_detail, $item_id, $location_id);
	}

	/*
	* Set to 0 all quantity in the given item
	*/
	public function reset_quantity($item_id)
	{
        $builder->where('item_id', $item_id);

        return $builder->update('item_quantities', array('quantity' => 0));
	}

	/*
	* Set to 0 all quantity in the given list of items
	*/
	public function reset_quantity_list($item_ids)
	{
        $builder->whereIn('item_id', $item_ids);

        return $builder->update('item_quantities', array('quantity' => 0));
	}
}
?>
