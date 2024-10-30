"use strict";

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
var __ = wp.i18n.__;
var useEffect = wp.element.useEffect;
var _ref = wp.blockEditor || wp.editor,
  InspectorControls = _ref.InspectorControls;
var _wp$components = wp.components,
  Flex = _wp$components.Flex,
  FlexItem = _wp$components.FlexItem,
  SelectControl = _wp$components.SelectControl,
  Placeholder = _wp$components.Placeholder,
  Spinner = _wp$components.Spinner,
  PanelBody = _wp$components.PanelBody;
var defaultCodeId = 'code-#########';

// Register the block
wp.blocks.registerBlockType('ci/inject', {
  title: __('Code Injection', 'code-injection'),
  description: __('Inject code snippets in HTML, CSS, and JavaScript.', 'code-injection'),
  icon: 'shortcode',
  category: 'widgets',
  attributes: {
    codeId: {
      type: 'string',
      "default": defaultCodeId
    }
  },
  edit: function edit(props) {
    var codes = _ci.codes || [];
    if (!Array.isArray(codes)) {
      codes = Object.values(codes);
    }

    // Generate options for the select control
    var options = [{
      value: defaultCodeId,
      label: __('— Select —', 'code-injection')
    }];
    codes.forEach(function (code) {
      options.push({
        value: code.value,
        label: code.title
      });
    });

    // Add state to track whether we're loading data from the server
    var _wp$element$useState = wp.element.useState(false),
      _wp$element$useState2 = _slicedToArray(_wp$element$useState, 2),
      isLoading = _wp$element$useState2[0],
      setIsLoading = _wp$element$useState2[1];

    // Add state to store the rendered HTML from the server
    var _wp$element$useState3 = wp.element.useState(''),
      _wp$element$useState4 = _slicedToArray(_wp$element$useState3, 2),
      renderedHtml = _wp$element$useState4[0],
      setRenderedHtml = _wp$element$useState4[1];

    // Use the useEffect hook to handle block selection changes
    useEffect(function () {
      if (props.attributes.codeId !== defaultCodeId) {
        // Start loading data from the server
        setIsLoading(true);
        wp.apiFetch({
          path: '/ci/v1/render-code',
          method: 'POST',
          data: {
            codeId: props.attributes.codeId
          }
        }).then(function (response) {
          // Strip scripts from the HTML
          var strippedHtml = response.html.replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '');
          // Update the block's content with the PHP execution result
          setRenderedHtml(strippedHtml);
          setIsLoading(false);
        });
      } else {
        setIsLoading(false);
        setRenderedHtml('');
      }
    }, [props.attributes.codeId]);
    return /*#__PURE__*/React.createElement("div", {
      tabIndex: "0"
    }, /*#__PURE__*/React.createElement(InspectorControls, null, /*#__PURE__*/React.createElement(PanelBody, {
      title: __('Code Injection Settings', 'code-injection')
    }, /*#__PURE__*/React.createElement(SelectControl, {
      label: __('Code ID/Slug:', 'code-injection'),
      value: props.attributes.codeId,
      options: options,
      onChange: function onChange(value) {
        return props.setAttributes({
          codeId: value
        });
      }
    }))), isLoading ? /*#__PURE__*/React.createElement(Placeholder, null, /*#__PURE__*/React.createElement(Flex, {
      justify: "center"
    }, /*#__PURE__*/React.createElement(FlexItem, null, /*#__PURE__*/React.createElement(Spinner, null)))) : !isLoading && !renderedHtml && props.attributes.codeId !== defaultCodeId ? /*#__PURE__*/React.createElement(Placeholder, null, /*#__PURE__*/React.createElement(Flex, {
      justify: "center"
    }, /*#__PURE__*/React.createElement(FlexItem, null, /*#__PURE__*/React.createElement("p", null, " ", __("Unable to render the code <".concat(props.attributes.codeId, ">"), 'code-injection'), " ")))) : !isLoading && (!renderedHtml || props.attributes.codeId === defaultCodeId) ? /*#__PURE__*/React.createElement(Placeholder, {
      withIllustration: "true"
    }) : /*#__PURE__*/React.createElement("div", {
      className: props.className,
      dangerouslySetInnerHTML: {
        __html: renderedHtml
      }
    }));
  },
  save: function save(props) {
    // Return null to render the block on the front end with PHP
    return null;
  }
});
