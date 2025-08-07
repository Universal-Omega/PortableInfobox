/**
 * This is a really hacky way to display PortableInfoboxes in the VisualEditor. 
 * It works by retrieving the wikitext from the Parsoid data-mw argument and calling
 * out to the legacy parser to get back the HTML which should be displayed in the editor. 
 * 
 * Note: this is a tempoary workaround to get this working until Parsoid is patched to 
 * support extensions like this
 */
ve.ce.MWPortableInfoboxTransclusion = function VeCeMWPortableInfoboxTransclusion() {
    ve.ce.MWPortableInfoboxTransclusion.super.apply( this, arguments );
}

OO.inheritClass( ve.ce.MWPortableInfoboxTransclusion, ve.ce.MWTransclusionBlockNode );

ve.ce.MWPortableInfoboxTransclusion.static.primaryCommandName = 'mwVEPI';

ve.ce.MWPortableInfoboxTransclusion.prototype.onSetup = function () {
    const modelNode = this.getModel();

    const mwData = modelNode.getAttribute('mw');

    if (mwData && mwData.parts) {
        // Extract the wikitext from the model -- must be a better way to do this, surely?
        const params = [];
        mwData.parts.forEach(part => {
            if (part.template) {
                Object.entries(part.template.params).forEach(([key, value]) => {
                    params.push(`|${key}=${value.wt}`);
                });
            }
        });

        const wikitext = `{{${mwData.parts[0].template.target.wt}${params.join('')}}}`;

        /**
         * Send this wikitext to the MW API and pray we get HTML back
         */
        new mw.Api().post({
            action: 'parse',
            text: wikitext,
            contentmodel: 'wikitext',
            format: 'json'
        }).then((response) => {
            if (response.parse && response.parse.text) {
                // replace the stub with the HTML, somehow its floated to the left, but I'm not sure why?!
                const html = response.parse.text['*'];
                this.$element.empty().append(html);
            }
        }).catch((error) => {
            console.error('Failed to parse infobox:', error);
        });
    }

    // Parent method
    ve.ce.MWPortableInfoboxTransclusion.super.prototype.onSetup.call( this );
};


ve.ce.nodeFactory.register( ve.ce.MWPortableInfoboxTransclusion );