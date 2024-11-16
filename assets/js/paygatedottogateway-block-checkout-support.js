( function( blocks, i18n, element, components, editor ) {
    const { registerPaymentMethod } = wc.wcBlocksRegistry;
    // Use the localized data from PHP
    const paygatedottogateways = paygatedottogatewayData || [];
    paygatedottogateways.forEach( ( paygatedottogateway ) => {
        registerPaymentMethod({
            name: paygatedottogateway.id,
            label: paygatedottogateway.label,
            ariaLabel: paygatedottogateway.label,
            content: element.createElement(
                'div',
                { className: 'paygatedottogateway-method-wrapper' },
                element.createElement( 
                    'div', 
                    { className: 'paygatedottogateway-method-label' },
                    '' + paygatedottogateway.description 
                ),
                paygatedottogateway.icon_url ? element.createElement(
                    'img', 
                    { 
                        src: paygatedottogateway.icon_url,
                        alt: paygatedottogateway.label,
                        className: 'paygatedottogateway-method-icon'
                    }
                ) : null
            ),
            edit: element.createElement(
                'div',
                { className: 'paygatedottogateway-method-wrapper' },
                element.createElement( 
                    'div', 
                    { className: 'paygatedottogateway-method-label' },
                    '' + paygatedottogateway.description 
                ),
                paygatedottogateway.icon_url ? element.createElement(
                    'img', 
                    { 
                        src: paygatedottogateway.icon_url,
                        alt: paygatedottogateway.label,
                        className: 'paygatedottogateway-method-icon'
                    }
                ) : null
            ),
            canMakePayment: () => true,
        });
    });
} )(
    window.wp.blocks,
    window.wp.i18n,
    window.wp.element,
    window.wp.components,
    window.wp.blockEditor
);