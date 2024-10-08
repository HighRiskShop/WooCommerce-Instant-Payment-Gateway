( function( blocks, i18n, element, components, editor ) {
    const { registerPaymentMethod } = wc.wcBlocksRegistry;
    // Use the localized data from PHP
    const highriskshopgateways = highriskshopgatewayData || [];
    highriskshopgateways.forEach( ( highriskshopgateway ) => {
        registerPaymentMethod({
            name: highriskshopgateway.id,
            label: highriskshopgateway.label,
            ariaLabel: highriskshopgateway.label,
            content: element.createElement(
                'div',
                { className: 'highriskshopgateway-method-wrapper' },
                element.createElement( 
                    'div', 
                    { className: 'highriskshopgateway-method-label' },
                    '' + highriskshopgateway.description 
                ),
                highriskshopgateway.icon_url ? element.createElement(
                    'img', 
                    { 
                        src: highriskshopgateway.icon_url,
                        alt: highriskshopgateway.label,
                        className: 'highriskshopgateway-method-icon'
                    }
                ) : null
            ),
            edit: element.createElement(
                'div',
                { className: 'highriskshopgateway-method-wrapper' },
                element.createElement( 
                    'div', 
                    { className: 'highriskshopgateway-method-label' },
                    '' + highriskshopgateway.description 
                ),
                highriskshopgateway.icon_url ? element.createElement(
                    'img', 
                    { 
                        src: highriskshopgateway.icon_url,
                        alt: highriskshopgateway.label,
                        className: 'highriskshopgateway-method-icon'
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