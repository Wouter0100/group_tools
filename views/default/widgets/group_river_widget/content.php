<?php

	global $CONFIG;

	$widget = $vars["entity"];
	
	// which group
	if($widget->context != "groups"){
		if($group_guid = $widget->getMetadata("group_guid")){
			if(!is_array($group_guid)){
				$group_guid = array($group_guid);
			}
		} elseif($group_guid = $widget->group_guid) {
			$group_guid = array($group_guid);
		} else {
			$group_guid = array();
		}
	} else {
		$group_guid = array($widget->getOwnerGUID());
	}
	
	$group_guid = array_map("sanitise_int", $group_guid);
	if(($key = array_search(0, $group_guid)) !== false){
		unset($group_guid[$key]);
	}
	
	// get activity filter
	$activity_filter = $widget->getMetadata("activity_filter");
	if(empty($activity_filter)){
		// fallback to old situation
		$activity_filter = $widget->activity_filter;
		if($activity_filter == "all"){
			$activity_filter = array();
		}
	}
	
	if(!empty($activity_filter) && !is_array($activity_filter)){
		$activity_filter = array($activity_filter);
	} elseif(empty($activity_filter)){
		$activity_filter = array();
	}
	
	if(!empty($group_guid)){
		//get the number of items to display
		$limit = (int) $widget->num_display;
		if($limit < 1){
			$limit = 5;
		}
			
		$offset = 0;
		
		$sql = "SELECT {$CONFIG->dbprefix}river.*";
		$sql .= " FROM {$CONFIG->dbprefix}river";
		$sql .= " INNER JOIN {$CONFIG->dbprefix}entities AS entities1 ON {$CONFIG->dbprefix}river.object_guid = entities1.guid";
		$sql .= " WHERE (entities1.container_guid in (" . implode(",", $group_guid) . ")";
		$sql .= " OR {$CONFIG->dbprefix}river.object_guid IN (" . implode(",", $group_guid) . "))";
		
		if(!empty($activity_filter)){
			$filter_wheres = array();
			
			foreach($activity_filter as $filter){
				list($type, $subtype) = explode(",", $filter);
				
				if(!empty($type)){
					$filter_where = " ({$CONFIG->dbprefix}river.type = '" . sanitise_string($type) . "'";
					
					if(!empty($subtype)){
						$filter_where .= " AND {$CONFIG->dbprefix}river.subtype = '" . sanitise_string($subtype) . "'";
					}
					
					$filter_where .= ")";
					$filter_wheres[] = $filter_where;
				}
			}
			
			if(!empty($filter_wheres)){
				$sql .= " AND (" . implode(" OR ", $filter_wheres) . ")";
			}
		}
		
		$sql .= " AND " . get_access_sql_suffix("entities1");
		$sql .= " ORDER BY {$CONFIG->dbprefix}river.posted DESC";
		$sql .= " LIMIT {$offset},{$limit}";

		$items = get_data($sql);

        if (!empty($items)) {
			$river_items = elgg_view('river/item/list',array(
												'limit' => $limit,
												'offset' => $offset,
												'items' => $items,
												'pagination' => false
											));
		} else {
			$river_items = elgg_echo("widgets:group_river_widget:view:noactivity");
		}

		//display
		echo elgg_view("page_elements/contentwrapper", array("body" => $river_items));
	} else {
		echo elgg_view("page_elements/contentwrapper", array("body" => elgg_echo("widgets:group_river_widget:view:not_configured")));
	}
	