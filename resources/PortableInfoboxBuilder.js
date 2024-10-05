(function (window, $) {
	'use strict';

	const CLASS_ITEMSELECTED = 'pi-ib-itemselected';
	const MSG_PREFIX = 'infoboxbuilder-';

	const XSL_STYLESHEET = new DOMParser().parseFromString(
		'<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">\
			<xsl:output indent="yes" omit-xml-declaration="yes"/>\
			<xsl:template match="node()|@*">\
				<xsl:copy>\
					<xsl:apply-templates select="node()|@*"/>\
				</xsl:copy>\
			</xsl:template>\
		</xsl:stylesheet>',
		'text/xml'
	);

	window.mediaWiki.PortableInfoboxBuilder = window.mediaWiki.PortableInfoboxBuilder || {};
	var Nodes = window.mediaWiki.PortableInfoboxBuilder.Nodes;

	class PortableInfoboxBuilder {
		constructor( config ) {
			this.config = config;

			this.api = new mw.Api();
			this.xmlSerializer = new XMLSerializer();

			if ( window.XSLTProcessor ) {
				this.xsltProcessor = new XSLTProcessor();
				this.xsltProcessor.importStylesheet( XSL_STYLESHEET );
			}

			this.infobox = new Nodes.NodeInfobox( this );
			this.infobox.children = [
				Nodes.Node.factory( this.infobox.markupDoc, 'title' ),
				Nodes.Node.factory( this.infobox.markupDoc, 'media' ),
				Nodes.Node.factory( this.infobox.markupDoc, 'header' ),
				Nodes.Node.factory( this.infobox.markupDoc, 'data' ),
				Nodes.Node.factory( this.infobox.markupDoc, 'data' )
			];

			mw.hook( 'portableinfoboxbuilder.nodeselect' ).add( ( node ) => this.select( node ) );
			this.$element = this.buildUI();
		}

		msg( key, params = [], optional = false ) {
			if ( !( params instanceof Array ) ) {
				params = [ params ];
			}
			params.unshift( MSG_PREFIX + key );

			let msg = mw.message.apply( mw, params );

			if ( optional && !msg.exists() ) {
				return undefined;
			}

			return msg.text()
		}

		buildUI() {
			var menuLayout = new OO.ui.MenuLayout( { menuPosition: 'after' } ),
				self = this;

			menuLayout.$menu.append(
				this.buildNewNodesMenu(),
				this.buildNodeMenu(),
				this.buildActionsMenu()
			);
			menuLayout.$content.append(
				new OO.ui.PanelLayout( { padded: true } ).$element.append(
					this.infobox.html()
				).click( function( e ) {
					if( e.target != this ) { return; }
					self.deselect()
				} )
			);

			this.$element = new OO.ui.PanelLayout( {
				framed: true,
				expanded: false,
				id: 'mw-infoboxbuilder'
			} ).$element.append( menuLayout.$element );

			return this.$element;
		}

		buildActionsMenu() {
			return new OO.ui.PanelLayout( { padded: true, expanded: false } ).$element.append(
				new OO.ui.ButtonWidget( {
					label: this.msg( 'action-clear' ),
					icon: 'trash',
					flags: [ 'primary', 'destructive' ]
				} ).on( 'click', () => { this.clearInfobox() } ).$element,
				new OO.ui.ButtonWidget( {
					label: this.msg( 'action-publish' ),
					icon: 'check',
					flags: [ 'primary', 'progressive' ]
				} ).on( 'click', () => { this.publishInfobox() } ).$element
			)
		}

		buildNewNodesMenu() {
			let menu = new OO.ui.PanelLayout( { padded: true, expanded: false } ),
				$menu = menu.$element.append(
					new OO.ui.LabelWidget( { label: this.msg( 'action-addnode' ) } ).$element
				);

			Nodes.NODE_LIST.forEach( ( e ) => {
				$menu.append(
					new OO.ui.ButtonWidget( { label: this.msg( 'node-' + e ) } )
						.on( 'click', () => this.addInfoboxElem( e ) )
						.$element
				)
			} );

			return $menu;
		}

		buildNodeMenu() {
			this.nodeInputSource = new OO.ui.TextInputWidget( {
				placeholder: this.msg( 'nodeparam-source' ),
				disabled: true
			} );
			this.nodeInputLabel = new OO.ui.TextInputWidget( {
				placeholder: this.msg( 'nodeparam-label' ),
				disabled: true
			} );
			this.nodeInputFormat = new OO.ui.TextInputWidget( {
				placeholder: this.msg( 'nodeparam-format' ),
				disabled: true
			} );
			this.nodeInputDefault = new OO.ui.TextInputWidget( {
				placeholder: this.msg( 'nodeparam-default' ),
				disabled: true
			} );
			this.nodeInputValue = new OO.ui.TextInputWidget( {
				placeholder: this.msg( 'nodeparam-value' ),
				disabled: true
			} );
			this.deleteNodeButton = new OO.ui.ButtonWidget( {
				label: this.msg( 'action-deletenode' ),
				icon: 'trash',
				disabled: true
			} ).on( 'click', () => this.deleteSelectedNode() );

			return new OO.ui.PanelLayout( { padded: true, expanded: false } ).$element.append(
				new OO.ui.FieldLayout( this.nodeInputSource, {
					label: this.msg( 'nodeparam-source' ),
					align: 'top',
					help: this.msg( 'nodeparamhelp-source', [], true ),
					disabled: true
				} ).$element,
				new OO.ui.FieldLayout( this.nodeInputLabel, {
					label: this.msg( 'nodeparam-label' ),
					align: 'top',
					help: this.msg( 'nodeparamhelp-label', [], true ),
					disabled: true
				} ).$element,
				new OO.ui.FieldLayout( this.nodeInputFormat, {
					label: this.msg( 'nodeparam-format' ),
					align: 'top',
					help: this.msg( 'nodeparamhelp-format', [], true ),
					disabled: true
				} ).$element,
				new OO.ui.FieldLayout( this.nodeInputDefault, {
					label: this.msg( 'nodeparam-default' ),
					align: 'top',
					help: this.msg( 'nodeparamhelp-default', [], true ),
					disabled: true
				} ).$element,
				new OO.ui.FieldLayout( this.nodeInputValue, {
					label: this.msg( 'nodeparam-value' ),
					align: 'top',
					help: this.msg( 'nodeparamhelp-value', [], true ),
					disabled: true
				} ).$element,
				this.deleteNodeButton.$element
			)
		}

		toggleNodeMenu( supports = false ) {
			if ( supports ) {
				this.deleteNodeButton.setDisabled( false );
			} else {
				this.deleteNodeButton.setDisabled( true );
				supports = {};
			}

			this.toggleNodeMenuWidget( this.nodeInputSource, supports.source, 'source' );
			this.toggleNodeMenuWidget( this.nodeInputLabel, supports.label, 'label' );
			this.toggleNodeMenuWidget( this.nodeInputFormat, supports.format, 'format' );
			this.toggleNodeMenuWidget( this.nodeInputDefault, supports.default, 'default' );
			this.toggleNodeMenuWidget( this.nodeInputValue, supports.value, 'value' );
		}

		toggleNodeMenuWidget( widget, enabled, param ) {
			widget.off( 'change' ).setDisabled( !enabled );

			if ( enabled ) {
				widget
					.setValue( this.selectedNode.params[param] )
					.on( 'change', ( value ) => this.selectedNode.changeParam( param, value ) );
			}
		}

		addInfoboxElem( type ) {
			this.deselect();
			this.infobox.createChildren( type );
		}

		select( node ) {
			if( this.selectedNode !== undefined ) {
				this.selectedNode.deselect();
			}
			this.selectedNode = node;
			this.toggleNodeMenu( node.supports() );
			this.infobox.element.classList.add( CLASS_ITEMSELECTED );
		}

		deselect() {
			if( this.selectedNode === undefined ) {
				return;
			}
			this.selectedNode.deselect();
			this.selectedNode = undefined;
			this.toggleNodeMenu();
			this.infobox.element.classList.remove( CLASS_ITEMSELECTED );
		}

		deleteSelectedNode() {
			this.infobox.removeChildren( this.selectedNode );
			this.deselect();
		}

		clearInfobox() {
			OO.ui.confirm( mw.message( 'confirmable-confirm', mw.config.get("wgUserName") ).text() )
				.done( ( confirmed ) => {
					if ( confirmed ) {
						this.deselect();
						this.infobox.clearChildren();
					}
				}
			);
		}

		getInfoboxMarkup() {
			let markup = this.infobox.markup();

			if ( this.xsltProcessor ) {
				let transformed = this.xsltProcessor.transformToDocument( markup );

				if ( transformed ) {
					let result = this.xmlSerializer.serializeToString( transformed )
						.replace( /(?<=^ *)  /mg, '\t' );

					if ( result.indexOf( '<transformiix:result' ) > -1 ) {
						return '<infobox>' + result.substring( result.indexOf( '>' ) + 1, result.lastIndexOf( '<' ) ) + '</infobox>';
					}

					return result;
				}
			}

			return this.xmlSerializer.serializeToString( markup );
		}

		publishInfobox() {
			OO.ui.prompt( this.msg( 'templatename' ), {
				size: 'large',
				textInput: {
					placeholder: this.msg( 'templatename' ),
					value: this.config.title,
					required: true
				}
			} ).done( ( title ) => {
				if ( title === null || title.trim() === '' ) {
					return;
				}

				let template = mw.config.get( 'wgFormattedNamespaces' )[10],
					namespace = ( title.substring( 0, template.length ) !== template ? ( template + ':' ) : '' );

				this.api.postWithToken( 'csrf', {
					action: 'edit',
					title: namespace + title,
					text: this.getInfoboxMarkup(),
					summary: this.msg( 'editsummary' ),
					notminor: true,
					recreate: true,
					createonly: true
				} ).done( () => {
					window.location.assign( mw.config.get( 'wgArticlePath' ).replace( '$1', namespace + title ) )
				} ).fail( ( code, err ) => {
					OO.ui.alert(
						err.error && err.error.info ? this.msg( 'editerror', err.error.info ) :
							this.msg( 'editerrorunknown' ),
						{ size: 'large' }
					);
				} );
			} );
		}
	}

	window.mediaWiki.PortableInfoboxBuilder.Builder = PortableInfoboxBuilder;

	mw.loader.using( 'oojs-ui-core' ).done( function() {
		let content = document.getElementById( 'mw-infoboxbuilder' );
		if ( content ) {
			let builder = new PortableInfoboxBuilder( {
				title: content.dataset.title
			} );
			content.replaceWith( builder.$element[0] );
		}
	} );
})(window, jQuery);
