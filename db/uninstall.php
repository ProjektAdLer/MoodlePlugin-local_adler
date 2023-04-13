<?php

use local_adler\local\section\db as section_db;

defined('MOODLE_INTERNAL') || die();


/**
 * This is called at the beginning of the uninstallation process.
 *
 * @return bool true if success
 * @throws dml_exception
 */
function xmldb_local_adler_uninstall(): bool {
    // get all adler section records
    $adler_sections = section_db::get_adler_sections();

    // get all sections for $adler_sections
    $sections = array_map(function($adler_section) {
        return section_db::get_moodle_section($adler_section->section_id);
    }, $adler_sections);

    // update availability condition
    foreach ($sections as $section) {
        if (empty($section->availability)) {
            continue;
        }
        // remove adler objects from availability condition
        $updated_availability = remove_adler_objects($section->availability);
        // update availability condition
        $section->availability = $updated_availability;
        // update section
        section_db::update_moodle_section($section);
    }

    return true;
}


/** Remove objects of type adler from the c attribute of this $condition object.
 * If it's the topmost level and "op" is "&", the corresponding element form "showc" attribute is also removed.
 * If there's another grouping operator below this level ("op" exists and is "&" or "|" and "c" exists),
 * the function is called recursively.
 *
 * @param object $condition the availability condition object
 */
function remove_adler_objects_inner_func(object $condition) {
    // iterate over all conditions (objects) in $obj->c
    for ($i = 0; $i < count($condition->c); $i++) {
        // check if $obj->c[$i] is an adler object
        if (property_exists($condition->c[$i], 'type') && $condition->c[$i]->type === 'adler') {
            // remove adler object from $obj->c
            unset($condition->c[$i]);
            $condition->c = array_values($condition->c);
            // if showc attribute exists on this condition level (meaning it's the out most one and "op" is of type "&")
            // remove corresponding element from $obj->showc
            if (property_exists($condition, 'showc')) {
                unset($condition->showc[$i]);
                $condition->showc = array_values($condition->showc);
            }
            // decrement $i because an element has been removed from $obj->c
            $i--;
            continue;
        }

        // check if $obj->c[$i] is a grouping operator ("op" exists and is "&" or "|" and "c" exists)
        if (
            property_exists($condition->c[$i], 'op')
            && ($condition->c[$i]->op === '&' || $condition->c[$i]->op === '|')
            && property_exists($condition->c[$i], 'c')
        ) {
            // call function recursively
            remove_adler_objects_inner_func($condition->c[$i]);
            // if there's no rule left in the sub-object, unset it.
            // this can't be done in the recursively called function because
            // the object passed to it can't be unset from inside that function.
            if (empty($condition->c[$i]->c)) {
                unset($condition->c[$i]);
                $condition->c = array_values($condition->c);
                // if showc attribute exists on this condition level (meaning it's the out most one and "op" is of type "&")
                // remove corresponding element from $obj->showc
                if (property_exists($condition, 'showc')) {
                    unset($condition->showc[$i]);
                    $condition->showc = array_values($condition->showc);
                }
                // decrement $i because an element has been removed from $obj->c
                $i--;
            }
        }
    }
}

/** Remove availability conditions of type adler from $condition.
 *
 * @param string $condition the availability condition
 * @return string the availability condition without adler conditions
 */
function remove_adler_objects(string $condition): ?string {
    $condition = json_decode($condition);

    remove_adler_objects_inner_func($condition);

    // unsetting the object passed to a sub-function via reference is not possible from inside that function.
    // -> If there's no rule left in the main object, unset it.
    if (empty($condition->c)) {
        return null;
    }
    return json_encode($condition);
}