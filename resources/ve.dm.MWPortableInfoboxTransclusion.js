/**
 * see ve.cd.MWPortableInfoboxTransclusion.js for the reasoning for this script
 */
ve.dm.MWPortableInfoboxTransclusion = function MWPortableInfoboxTransclusion() {
    ve.dm.MWPortableInfoboxTransclusion.super.apply( this, arguments );
}

OO.inheritClass( ve.dm.MWPortableInfoboxTransclusion, ve.dm.MWTransclusionBlockNode );

ve.dm.MWPortableInfoboxTransclusion.static.name = "mwPI";

ve.dm.MWPortableInfoboxTransclusion.static.matchTagNames = null;

ve.dm.MWPortableInfoboxTransclusion.static.extensionName = 'infobox';

ve.dm.MWPortableInfoboxTransclusion.static.matchRdfaTypes = [ 'mw:Transclusion' ];

ve.dm.MWPortableInfoboxTransclusion.static.matchFunction = function ( domElement ) {
    const about = domElement.getAttribute('about');

    const match = ve.init.target.doc.querySelector( "p[about=\"" + about + "\"][class*='portable-infobox'], p[about=\"" + about + "\"][class*='mw-empty-elt']" );

    return !!match;
}

ve.dm.modelRegistry.register( ve.dm.MWPortableInfoboxTransclusion );