 
// CKEDITOR.editorConfig = function(config) {
// // 	config.removeButtons = 'Cut,Copy,Paste,Undo,Redo,Anchor';
// 	config.toolbar = [['Bold', 'Italic', '-',
// 								'NumberedList', 'BulletedList', '-',
// 								'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock', '-',
// 								'Link', 'Unlink', '-',
// 								'Table','Smiley']];
// 	config.extraPlugins = ['Justify'];
// };

// CKEDITOR.editorConfig = function( config ) {
// 	config.toolbarGroups = [
// // 	{ name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
// // 	{ name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
// // 	{ name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
// // 	{ name: 'forms', groups: [ 'forms' ] },
// 	{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
// 	{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
// 	{ name: 'links', groups: [ 'links' ] },
// 	{ name: 'insert', groups: [ 'insert' ] },
// 	{ name: 'styles', groups: [ 'styles' ] },
// 	{ name: 'colors', groups: [ 'colors' ] },
// 	{ name: 'tools', groups: [ 'tools' ] },
// 	{ name: 'others', groups: [ 'others' ] },
// 	{ name: 'about', groups: [ 'about' ] }
// 	];
//
// 	config.removeButtons = 'Strike,Subscript,Superscript,CopyFormatting,RemoveFormat,Outdent,Indent,Blockquote,CreateDiv,BidiLtr,BidiRtl,Language,Anchor,Flash,Smiley,SpecialChar,PageBreak,Iframe,FontSize,Maximize,ShowBlocks,About,Font';
// };

CKEDITOR.editorConfig = function( config ) {
	config.toolbar = [
		{ name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline' ] },
		{ name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
		{ name: 'links', items: [ 'Link', 'Unlink' ] },
		{ name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule' ] },
		{ name: 'styles', items: [ 'Format', 'Styles' ] },
		{ name: 'colors', items: [ 'TextColor', 'BGColor' ] }
	];
	config.extraPlugins = 'justify';
};
