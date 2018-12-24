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

      edit: function() {
        return el('p', {style: blockStyle}, 'Hello world');
      },

      save: function() {
        return el('p', {style:blockStyle}, 'Saved content');
      }
    });
