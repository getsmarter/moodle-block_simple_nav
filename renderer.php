<?php


/**
 * Used to render the navigation items in the simple_nav block
 */
class block_simple_nav_renderer extends plugin_renderer_base {

    public function simple_nav_tree($items) {
        $depth = 0;
        $type = 0;
        $content = array();
        $doc = new DOMDocument();
        $node = $doc->createElement('ul');
        $mainnode = $doc->appendChild($node);
        $mainnode->setAttribute('class', 'block_tree list');
        $coursenode = null;
        
        for ($i = 0; $i < count($items); $i++) {
            $item = $items[$i];
            $externalnode = $this->sn_print_item($item);
            if (!is_null($externalnode)) {
                $childnode = $doc->importNode($externalnode, true);
                
                if ($item['mytype'] == "category" || $item['mytype'] == "home" ||
                         $item['mytype'] == "nohome") {
                    $categorynode = $mainnode->appendChild($childnode);
                    $ul = $doc->createElement('ul');
                    $categorynode = $categorynode->appendChild($ul);
                } else if ($item['mytype'] == "course") {
                    $coursenode = $categorynode->appendChild($childnode);
                    $ul = $doc->createElement('ul');
                    $coursenode = $coursenode->appendChild($ul);
                } else if ($item['mytype'] == "module") {
                    if (!is_null($coursenode)) {
                        $coursenode->appendChild($childnode);
                    } else {
                        $categorynode->appendChild($childnode);
                    }
                }
            }
        }
        
        // cleanup empty nodes
        $xpath = new DOMXPath($doc);
        foreach ($xpath->query('//ul[not(node())]') as $node) {
            $node->parentNode->removeChild($node);
        }
        $content = $doc->saveHTML();
        return $content;
    }

    /**
     * Render items for display in the simple nav block
     * @param array $item
     * @return DOMDocument 
     */
    protected function sn_print_item(array $item) {
        global $CFG, $OUTPUT;
        $doc = new DOMDocument();
        $img = null;
        $baseurl = $CFG->wwwroot;
        $mystartclass = "";
        $itemclass = $item['myclass'];
        $itemtype = $item['mytype'];
        $itemdepth = $item['mydepth'];
        $itemvisibility = $item['myvisibility'];
        $itemicon = $item['myicon'];
        $itemname = $item['myname'];
        $itemid = $item['myid'];
        
        
        if (!empty($this->config->space)) {
            $space_symbol = $this->config->space;
        }
        
        // if we don't want to show the first node, we use the class "startingpoint" as an indicator
        // to totally skip it
        if (strpos($itemclass, 'startingpoint') !== false) {
            return null;
        }
        
        // we only want the active branch to be open, all the other ones whould be collapsed
        $mycollapsed = '';
        // myclass only has a value when it's active
        if (!$itemclass) {
            $mycollapsed = ' collapsed';
        } else {
            $mycollapsed = '';
        }
        
        // sometimes, we don't show categories by simple setting their name to "". If this is the
        // case, we want them not to be collapsed.
        // Here is a simple way to do so:
        // If the Name is empty, we also set the class, which controls the collapsed/uncollapsed
        // status, to "".
        if (empty($itemname)) {
            $mycollapsed = "";
        }
        
        // is it a category
        if ($itemtype == 'category') {
            $myurl = $CFG->wwwroot . '/course/index.php?categoryid=' . $itemid;
            $itemclass_li = 'type_category depth_' . $itemdepth . '' . $mycollapsed . ' contains_branch' .
                     $mystartclass;
            $itemclass_p = 'tree_item branch' . $itemclass;
            $itemclass_a = '';
            if ($itemvisibility == 0) {
                $itemclass_a = 'class="dimmed_text"';
            } else {
                $itemclass_a = '';
            }
        }        // is it a course
        elseif ($itemtype == 'course') {
            // We don't want course-nodes to be open, even when they are active so:
            // $mycollapsed =' collapsed';
            $myurl = $CFG->wwwroot . '/course/view.php?id=' . $itemid;
            $itemclass_li = 'type_course depth_' . $itemdepth . '' . $mycollapsed . ' contains_branch';
            ;
            $itemclass_p = 'tree_item branch hasicon' . $itemclass;
            if ($itemvisibility == 0) {
                $itemclass_a = 'class="dimmed_text"';
            } else {
                $itemclass_a = '';
            }
        }        // or the home node
        elseif ($itemtype == 'home') {
            $myurl = $CFG->wwwroot;
            $itemclass_li = 'type_unknown depth_1 contains_branch';
            $itemclass_p = 'tree_item branch ' . $itemclass . ' navigation_node';
            $itemclass_a = '';
        }        // or invisible home node
        elseif ($itemtype == 'nohome') {
            $myurl = $CFG->wwwroot;
            $itemclass_li = 'type_unknown depth_1 contains_branch simple_invisible';
            $itemclass_p = 'tree_item branch ' . $itemclass . ' navigation_node';
            $itemclass_a = '';
        }        // or a module
        elseif ($itemtype == 'module') {
            $myurl = $CFG->wwwroot . '/mod/' . $itemicon . '/view.php?id=' . $itemid;
            $itemclass_li = 'contains_branch item_with_icon';
            $itemclass_p = 'tree_item leaf hasicon' . $itemclass;
            $itemclass_a = '';
            
            if ($itemvisibility == 0) {
                $itemclass_a = 'class="dimmed_text"';
            } else {
                $itemclass_a = '';
            }
            $displayoption = get_config('block_simple_nav', 'displayoptions');
            $img = $doc->createElement('img');
            $img->setAttribute('alt', "");
            if ($displayoption === '1') {
                $img->setAttribute('class', "smallicon navicon");
                $img->setAttribute('src', 
                        $baseurl .
                                 '/theme/image.php?theme=standard&amp;image=icon&amp;rev=295&amp;component=' .
                                 $itemicon);
            } else if ($displayoption === '2') {
                $img->setAttribute('class', "smallicon navicon");
                $img->setAttribute('src', 
                        $baseurl . '/theme/image.php?theme=' . $CFG->theme .
                                 '&amp;image=t/collapsed&amp;rev=295&amp;component=core');
            } else {
                $img->setAttribute('class', "smallicon navicon navigationitem");
                $img->setAttribute('src', 
                        $baseurl . '/theme/image.php?theme=' . $CFG->theme .
                                 '&amp;image=i/navigationitem&amp;rev=295&amp;component=core');
            }
        }
        
        $li = $doc->createElement('li');
        $li->setAttribute('class', $itemclass_li);
        $li = $doc->appendChild($li);
        $p = $doc->createElement('p');
        $p = $li->appendChild($p);
        $p->setAttribute('class', $itemclass_p);
        $text = $doc->createTextNode($itemname);
        $a = $doc->createElement('a');
        $a->appendChild($text);
        $a = $p->appendChild($a);
        
        $a->setAttribute('class', $itemclass_a);
        $a->setAttribute('href', $myurl);
        if (!is_null($img)) {
            $a->appendChild($img);
        }
        return $li;
    }
}
