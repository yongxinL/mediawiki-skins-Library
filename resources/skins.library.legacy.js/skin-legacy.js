var
	collapsibleTabs = require( './collapsibleTabs.js' ),
	library = require( './library.js' );

function main() {
	collapsibleTabs.init();
	$( library.init );
}

main();
