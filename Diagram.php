<?php
/*
 * Diagram
 */

require_once 'Color.php';
require_once 'Grid.php';

function DFDParseTokens($string){
	/*
	 * Takes a string and returns an array of tokens (e.g. Color tags, tiles, etc.)
	 */
	
	// True when inside a tag ([...])
	$in_tag = false;
	// Index where current tag begins
	$tag_start = 0;
	
	$tokens = array();
	
	for ($index = 0; $index < strlen($string); $index++) {
		$char = $string[$index];
		if ($char == "[") {
			// starts a tag
			$in_tag = true;
			$tag_start = $index;
		}
		elseif ($char == "]") {
			// closes a tag
			$in_tag = false;
			// Use the substring from $tag_start to the current character (INCLUSIVE) as the token
			$tokens[] = substr($string, $tag_start, $index - $tag_start + 1);
		}
		if($in_tag || $char == ']'){
			// Don't count tags as individual characters!
			continue;
		}
		//if (!$in_tag && $char != "]") {
			// Not in a tag, so current character is an individual token
		$tokens[] = $char;
		//}
	}
	return $tokens;
}

class DFDBlockFile{

	private $text;
	private $blocks;
	function __construct($path) {
		$this->text = file_get_contents($path);
		$matches = array();
		preg_match_all('/<(tile|block) name=".*">[\s\S]*?<\/\1>/', $this->text, $matches);
		$this->blocks = array();
		// $matches[0] is list ($1 is list of tile|block)
		foreach($matches as $index => $match){
			//print ">$match<\n";
			$type = $matches[1][$index];
			if ($type == 'tile') {
				$this->blocks[] = new DFDTile($matches[0][$index]);
			}
			
		}
	}
	
	function get_block($name){
		/*
		 * Returns a block in $this->block_list with the given name
		 */
		foreach($this->blocks as $block){
			if($block->name == $name){
				return $block;
			}
		}
		return null;
	}
}

class DFDTile {

	public $name;
	function __construct($text) {
		$lines = preg_split('/\n/', $text);
		if (count($lines) != 6) {
			trigger_error("Tag {$lines[0]} does not fit format! Skipping");
		}
		$tag = array();
		preg_match('/<tile name="(.*?)">/', $lines[0], $tag); 
		$this->name = $tag[1];
	}
}

class DFDTable {
	/**
	 * Represents the table created by a diagram
	 */
	private $text;
	private $opts;
	private $fg;
	private $bg;
	private $lines;
	private $grid;
	public function __construct($text, $a_opts) {
		// Default options
		$opts = array(
			'fg' => '7:1',
			'bg' => '0:0'
		);
		foreach($opts as $key => $val){
			if(array_key_exists($key, $a_opts)){
				$opts[$key] = $a_opts[$key];
			}
		}
		$this->text = $text;
		$this->opts = $opts;
		$this->fg = $opts['fg'];
		$this->bg = $opts['bg'];
		$this->setUp();
	}
	public function setUp(){
		/*
		 * Set up table
		 */
		
		$fgcolor = $this->fg;
		$bgcolor = $this->bg;
		
		$this->grid = new DGrid();
		$this->lines = preg_split('/\n/', $this->text);
		
		// Parse tokens
		$this->tokens = array();
		for ($row = 0; $row < count($this->lines); $row++) {
			$this->tokens[$row] = DFDParseTokens($this->lines[$row]);
		}
		
		/* foreach($this->lines as $row => $line){
			for($i = 0; $i < strlen($line); $i++) {
				$cell = new DFDTableCell($line[$i], $fgcolor, $bgcolor);
				$this->grid->set($row, $i, $cell);
			}
		} */
		
		for ($row = 0; $row < count($this->tokens); $row++) {
			$tokens = $this->tokens[$row];
			$col = -1;
			for ($i = 0; $i < count($tokens); $i++) {
				$token = $tokens[$i];
				if(strlen($token) == 1){
					// Character
					$col++;
					$cell = new DFDTableCell($token, $fgcolor, $bgcolor);
					$this->grid->set($row, $col, $cell);
				}
				else {
					// tag
					if ($token == '[#]') {
						// Reset foreground color
						$fgcolor = $this->fg;
					}
					elseif ($token == '[@]') {
						// Reset bg color
						$bgcolor = $this->bg;
					}
					elseif ($token == '[#@]' || $token == '[@#]') {
						// Reset fg and bg
						$bgcolor = $this->bg;
						$fgcolor = $this->fg;
					}
					elseif ($token[1] == '#') {
						// Set fg color
						$fgcolor = substr($token, 2, strlen($token) - 3);
					}
					elseif ($token[1] == '@') {
						// Set bg color
						$bgcolor = substr($token, 2, strlen($token) - 3);
					}
				}
			}			
		}
	}
	public function render(){
		$html = "\n<table>\n";
		for($r = 0; $r < $this->grid->height; $r++) {
			$html .= "\t<tr>";
			for ($c = 0; $c < $this->grid->width; $c++) {
				$cell = $this->grid->get($r, $c);
				if($cell === false){
					// No cell exists at this row/col; create a blank black cell
					$cell = new DFDTableCell(' ', '0:0', '0:0');
				}
				$html .= "<td>{$cell->render()}</td>";
			}
			$html .= "</tr>\n";
		}
		$html .= "</table>";
		return $html;
	}
}

class DFDTableCell {
	/*
	 * An individual cell in a table
	 */
	public $text;
	public $fg;
	public $bg;
	public function __construct($text, $fg, $bg) {
		$this->text = $text;
		$this->fg = new Color($fg);
		$this->bg = new Color($bg);
	}
	public function render(){
		$char = $this->text;
		if($char == ' '){
			$char = '&nbsp;';
		}
		return "<span style=\"display:block; color:{$this->fg}; background-color:{$this->bg};\">{$char}</span>";
	}
}

class DFDiagram {
	/**
	 * @description Diagram wrapper
	 */
	private $table;
	public function __construct($text, $opts) {
		// Initialize
		$this->table = new DFDTable($text, $opts);
	}
	public function render(){
		/* $html = 'Not implemented!';
		$html .= "<br>FG:{$this->fgcolor}, BG:{$this->bgcolor}";
		$html .= "<br>Text:<br> {$this->text}";
		 * 
		 */
		return $this->format($this->table->render());
	}

	public function format($html) {
		return <<< HTML
<div class="dfdiagram">
$html
</div>
HTML;
	}
	
	
}

class DFDMWHook {
	/*
	 * Hook into MediaWiki API
	 */
	static public function init($parser) {
		// Bind the <diagram> tag to DFDMWHook::create
		$parser->setHook('diagram', 'DFDMWHook::create');
		return true;
	}
	static public function create($text, $args, $parser, $frame) {
		// HTML-style ignoring of whitespace
		if(preg_match('/\S/', $text) === 0){ // no match
			// Include the default diagram
			global $wgDFDDefaultDiagramPath;
			$text = file_get_contents($wgDFDDefaultDiagramPath);
		}
		// Remove leading newlines
		$text = preg_replace('/^\n+/', '', $text);
		// Create new DFDiagram
		$diagram = new DFDiagram($text, $args);
		return $diagram->render();
	}
	static public function includeModules($outPage) {
		/*
		 * Include the resources in $wgResourceModules
		 */
		$outPage->addModuleStyles(array('ext.DFDiagram'));
		return true;
	}
}

$DFDFile = new DFDBlockFile($wgDFDConfigFile);
