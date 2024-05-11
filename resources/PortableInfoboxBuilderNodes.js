(function (window, $) {
	'use strict';

	const MSG_PREFIX = 'infoboxbuilder-';
	const NODE_ATTRIBUTES = [
		'accent-color-default',
		'accent-color-source',
		'accent-color-text-default',
		'accent-color-text-source',
		'audio',
		'image',
		'layout',
		'source',
		'span',
		'theme',
		'theme-source',
		'video'
	];
	const NODE_CONTENTNODES = [
		'label',
		'format',
		'default'
	];
	const NODE_IDPREFIX = 'pi-ib-node-';
	const NODE_CLASSSELECTED = 'pi-ib-selected';

	var nodeId = 0,
		dataId = 0;

	class NodeValidationError {
		constructor( message ) {
			this.message = message;
		}

		render() {
			let msg = document.createElement( 'div' );

			msg.className = 'pi-ib-error-message';
			msg.appendChild( new OO.ui.LabelWidget( { label: this.message } ).$element[0] );
			msg.appendChild( this.getIcon() );

			return msg;
		}

		getIcon() {
			let icon = new OO.ui.IconWidget( { icon: 'alert', flags: 'primary' } );
			// trigger inverted icon variant
			icon.isFramed = () => true;

			return icon.$element[0];
		}

		getClass() {
			return 'pi-ib-error';
		}
	}

	class NodeValidationWarning extends NodeValidationError {
		getIcon() {
			return new OO.ui.IconWidget( { icon: 'alert' } ).$element[0];
		}
		getClass() {
			return 'pi-ib-warning';
		}
	}

	class PINode {
		constructor( markupDoc, params ) {
			if ( this.constructor === PINode ) {
				throw new TypeError( 'Use Node.factory() instead, "Node" is an abstract class.' );
			}

			this.elementClasses = 'pi-item ';
			this.elementSelectable = true;
			this.elementTag = 'div';
			this.markupContentTag = false;
			this.markupDoc = markupDoc;
			this.markupTag = undefined;
			this.id = nodeId++;
			this.params = params || this.getDefaultParams();
			this.selected = false;
		}

		static factory( markupDoc, type, params ) {
			switch ( type ) {
				case 'data':
					return new NodeData( markupDoc, params );
				case 'title':
					return new NodeTitle( markupDoc, params );
				case 'media':
					return new NodeMedia( markupDoc, params );
				case 'header':
					return new NodeHeader( markupDoc, params );
				case 'navigation':
					return new NodeNavigation( markupDoc, params );
				case 'infobox':
					throw new TypeError( 'Use new NodeInfobox() instead.' );
				default:
					throw new TypeError( 'Unknown node type "' + type + '"' );
			}
		}

		getDefaultParams() {
			return {};
		}

		html() {
			if( this.element ) {
				this.element.innerHTML = '';
			} else {
				let element = document.createElement( this.elementTag );
				element.id = NODE_IDPREFIX + this.id;

				if ( this.elementSelectable ) {
					element.onmousedown = () => this.select();
				}

				this.element = element;
			}

			this.element.className = this.elementClasses;

			if ( this.selected && this.elementSelectable ) {
				this.element.classList.add( NODE_CLASSSELECTED );
			}

			try {
				this.validate();
			} catch ( error ) {
				if ( error instanceof NodeValidationError ) {
					this.element.classList.add( error.getClass() );
					this.element.appendChild( error.render() );
				} else {
					throw error;
				}
			}

			return this.element;
		}

		markup() {
			let node = this.markupDoc.createElement( this.markupTag ),
				supports = this.supports();

			NODE_ATTRIBUTES.forEach( ( a ) => {
				if ( supports[a] && this.params[a] ) {
					node.setAttribute( a, this.params[a] );
				}
			} );

			if ( this.markupContentTag && this.params.value ) {
				node.appendChild( this.markupDoc.createTextNode( this.params.value ) );
			} else {
				NODE_CONTENTNODES.forEach( ( n ) => {
					if ( supports[n] && this.params[n] ) {
						let subnode = this.markupDoc.createElement( n );
						subnode.appendChild( this.markupDoc.createTextNode( this.params[n] ) );
						node.appendChild( subnode );
					}
				} );
			}

			return node;
		}

		select() {
			if ( this.elementSelectable ) {
				mw.hook( 'portableinfoboxbuilder.nodeselect' ).fire( this );
				this.selected = true;
				this.element.classList.add( NODE_CLASSSELECTED );
			}
		}

		deselect() {
			if ( this.elementSelectable ) {
				this.selected = false;
				this.element.classList.remove( NODE_CLASSSELECTED );
			}
		}

		remove() {
			$( this.element ).remove();
		}

		supports() {
			return {};
		}

		validate() {
			if ( this.params.source?.match( /["|={}]/ ) ) {
				throw new NodeValidationError( this.msg( 'nodeerror-invalidsource' ) );
			}

			if ( !this.params.source && !this.params.default && !this.params.value ) {
				throw new NodeValidationWarning( this.msg( 'nodeerror-nosourceordefault' ) );
			}

			return true;
		}

		changeParam( param, value ) {
			if ( this.supports()[param] ) {
				this.params[param] = value;
				this.html();
			}
		}

		msg( key, params ) {
			if ( !( params instanceof Array ) ) {
				params = [ params ];
			}
			params.unshift( MSG_PREFIX + key );

			return mw.message.apply( mw, params ).text();
		}
	}

	class NodeData extends PINode {
		constructor( markupDoc, params ) {
			super( markupDoc, params );
			this.elementClasses += 'pi-data pi-item-spacing pi-border-color';
			this.markupTag = 'data';
		}

		getDefaultParams() {
			return {
				label: this.msg( 'nodeparam-label' ),
				source: this.msg( 'node-data' ).toLocaleLowerCase() + ( ++dataId )
			};
		}

		html() {
			super.html();

			if ( this.params.label ) {
				let label = document.createElement( 'h3' );
				label.className = 'pi-data-label pi-secondary-font';
				label.textContent = this.params.label;
				this.element.appendChild( label );
			}

			let value = document.createElement( 'div' );
			value.className = 'pi-data-value pi-font';

			// '{{{$1}}}' in msg throws an error with jqueryMsg enabled
			value.textContent =  this.params.source ?
				this.msg( 'node-data-value-source', '{{{' + this.params.source + '}}}' ) :
					this.params.default ? this.params.default : '';

			this.element.appendChild( value );

			return this.element;
		}

		supports() {
			return {
				default: true,
				format: true,
				label: true,
				layout: [ 'default' ],
				source: true,
				span: true
			};
		}
	}

	class NodeTitle extends PINode {
		constructor( markupDoc, params ) {
			super( markupDoc, params );
			this.elementTag = 'h2';
			this.elementClasses += 'pi-item-spacing pi-title';
			this.markupTag = 'title';
		}

		getDefaultParams() {
			return {
				source: this.msg( 'node-title' ).toLowerCase(),
				default: '{{PAGENAME}}'
			};
		}

		html() {
			super.html();

			this.element.textContent = this.params.default === '{{PAGENAME}}' ?
				this.msg( 'node-title-value-pagename' ) : this.msg( 'node-title-value' );

			return this.element;
		}

		supports() {
			return {
				source: true,
				format: true,
				default: true
			};
		}
	}

	class NodeMedia extends PINode {
		constructor( markupDoc, params ) {
			super( markupDoc, params );
			this.elementTag = 'figure';
			this.elementClasses += 'pi-media pi-image';
			this.markupTag = 'image';
		}

		getDefaultParams() {
			return {
				source: this.msg( 'node-media' ).toLowerCase()
			};
		}

		html() {
			super.html();

			let a = document.createElement( 'a' ),
				img = document.createElement( 'img' );

			a.className = 'image image-thumbnail';
			a.appendChild( img );
			img.className = 'pi-image-thumbnail';
			img.src = mw.config.get( 'wgExtensionAssetsPath' ) + '/../resources/assets/mediawiki.png';
			this.element.appendChild( a );

			return this.element;
		}

		supports() {
			return {
				source: true,
				audio: true,
				image: true,
				video: true,
				alt: true,
				caption: true,
				default: true
			};
		}
	}
	
	class NodeHeader extends PINode {
		constructor( markupDoc, params ) {
			super( markupDoc, params );
			this.elementTag = 'h2';
			this.elementClasses += 'pi-item-spacing pi-header pi-secondary-font pi-secondary-background';
			this.markupTag = 'header';
			this.markupContentTag = true;
		}

		getDefaultParams() {
			return {
				value: this.msg( 'node-header' )
			};
		}

		html() {
			super.html();

			this.element.textContent = this.params.value === this.msg( 'node-header' ) ?
				this.msg( 'node-header-value' ) : this.params.value;

			return this.element;
		}

		supports() {
			return {
				value: true
			};
		}
	}

	class NodeNavigation extends PINode {
		constructor( markupDoc, params ) {
			super( markupDoc, params );
			this.elementTag = 'nav';
			this.elementClasses = 'pi-navigation pi-item-spacing pi-secondary-background pi-secondary-font';
			this.markupTag = 'navigation';
			this.markupContentTag = true;
		}

		getDefaultParams() {
			return {
				value: this.msg( 'node-navigation' )
			};
		}

		html() {
			super.html();

			this.element.textContent = this.params.value;

			return this.element;
		}

		supports() {
			return {
				value: true
			};
		}
	}

	class NodeInfobox extends PINode {
		constructor( params ) {
			super( document.implementation.createDocument( '', '' ), params );

			this.children = [];
			this.elementClasses = 'portable-infobox pi-background';
			this.elementSelectable = false;
			this.elementTag = 'aside';
			this.markupTag = 'infobox';
		}

		addChildren( children ) {
			if ( children instanceof PINode ) {
				this.children.push( children );
				this.element.append( children.html() );
			}
		}

		createChildren( type, params ) {
			this.addChildren( PINode.factory( this.markupDoc, type, params ) );
		}

		clearChildren() {
			this.children = [];
			$( this.element ).empty();
			dataId = 0;
		}

		removeChildren( children ) {
			let i = this.children.indexOf( children );
			if ( i >= 0 ) {
				this.children.splice( i, 1 );
				$( children.element ).remove();
			}
		}

		reorderChildren( order, prefixed = false ) {
			let newChildren = [];
			this.children.forEach( c => {
				let i = order.indexOf( ( prefixed ? NODE_IDPREFIX : 0 ) + c.id );
				if ( i >= 0 ) {
					newChildren[i] = c;
				}
			} );
			this.children = newChildren;
		}

		html() {
			super.html();

			this.children.forEach( c => { this.element.appendChild( c.html() ) } );
			$( this.element ).sortable( {
				axis: 'y',
				containment: 'parent',
				cursor: 'move',
				scroll: false,
				tolerance: 'pointer',
				deactivate: ( e, ui ) => {
					ui.item.attr( 'style', '' );
					this.reorderChildren( $( this.element ).sortable( 'toArray' ), true );
				}
			} );

			return this.element;
		}

		markup() {
			let node = super.markup();
			this.children.forEach( c => { node.appendChild( c.markup() ) } );

			return node;
		}

		supports() {
			return {
				'accent-color-default': true,
				'accent-color-source': true,
				'accent-color-text-default': true,
				'accent-color-text-source': true,
				'layout': [ 'default', 'stacked' ],
				'theme': true,
				'theme-source': true
			};
		}

		validate() {}
	}

	window.mediaWiki.PortableInfoboxBuilder = window.mediaWiki.PortableInfoboxBuilder || {};
	window.mediaWiki.PortableInfoboxBuilder.Nodes = {
		Node: PINode,
		NodeData: NodeData,
		NodeMedia: NodeMedia,
		NodeTitle: NodeTitle,
		NodeInfobox: NodeInfobox,
		NODE_LIST: [ 'data', 'title', 'media', 'header', 'navigation' ]
	};
})(window, jQuery);
