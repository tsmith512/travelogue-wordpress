/**
 * Yes, this needs to be written in ES6 but that is not what the tutorial on
 * wordpress.org is written in, and I have only an hour to toss this together
 */
var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    blockStyle = { backgroundColor: "#CCC", color: "#333", padding: "20px" };

    registerBlockType('travelogue-gutenberg/link-card', {
      title: 'TL Link Card',
      icon: 'admin-links',
      category: 'embed',

      attributes: {
        title: {
          source: 'text',
          selector: '.card__title'
        },
        link: {
          source: 'text',
          selector: '.card__link'
        }
      }
/* BELOW THIS IS BROKEN */
/* WELL, ABOVE THIS IS BROKEN TOO */
      edit: function(props) {
        var content = props.attributes.content;
        function onChangeContent(newContent) {
          props.setAttributes({content: newContent});
        }
        return el(
          'p',
          {
            key: 'editable',
            tagName: 'p',
            style: blockStyle,
            onChange: onChangeContent,
            value: content,
          }
        );
      },

      save: function() {
        return el('p', {style:blockStyle}, 'Saved content');
      }
    });
