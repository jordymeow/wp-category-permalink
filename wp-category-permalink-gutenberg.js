// import {PluginPostStatusInfo} from "@wordpress/edit-post";
var __ = wp.i18n.__;
var PluginSidebar = wp.editPost.PluginSidebar,
    PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem,
    PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
var PanelBody = wp.components.PanelBody,
    TextControl = wp.components.TextControl,
    CheckboxControl = wp.components.CheckboxControl,
    SelectControl = wp.components.SelectControl;
var Component = wp.element.Component,
    Fragment = wp.element.Fragment;
var withSelect = wp.data.withSelect,
    withDispatch = wp.data.withDispatch;
var withState = wp.compose.withState,
    compose = wp.compose.compose;
var registerPlugin = wp.plugins.registerPlugin;

var CanonicalCategory = function CanonicalCategory(props) {
    return wp.element.createElement(
      Fragment, 
      null, 
      wp.element.createElement("p", null, "Canonical category"), 
      wp.element.createElement(
            SelectControl, 
            {
                value: props.canonicalCatId,
                options: props.categoryarray,
                onChange: function onChange(value) {
                    props.onCanonicalCatIdChanged(value);
                }
            }
        )
    );
};

CanonicalCategory = compose(
    [
        withSelect(function (select) {
            return {
                canonicalCatId: select('core/editor').getEditedPostAttribute('meta')['_category_permalink']
            };
        }), 
        withSelect(function (select) {
            return {
                postCategories: select('core/editor').getEditedPostAttribute('categories')
            };
        }), 
        withSelect(function (select, props) {
            var categoryarray = [];

            for (var i = 0; i < props.postCategories.length; i++) {
                var categorieId = props.postCategories[i];
                var category = select('core').getEntityRecord('taxonomy', 'category', categorieId);

                if (category) {
                    categoryarray.push({
                        label: category.name,
                        value: category.id
                    });
                } else {
                    categoryarray.push({
                        label: categorieId + ' - Name is loading',
                        value: categorieId
                    });
                }
            }

            return {
                categoryarray: categoryarray
            };
        }), 
        withDispatch(function (dispatch) {
            return {
                onCanonicalCatIdChanged: function onCanonicalCatIdChanged(canonicalCatId) {
                    dispatch('core/editor').editPost({
                        meta: {
                            _category_permalink: canonicalCatId
                        }
                    });
                }
            };
        })
    ])(CanonicalCategory);

registerPlugin('babymarkt-canonical-category', {
    icon: 'admin-site',
    render: function render() {
        return wp.element.createElement(
            PluginPostStatusInfo, 
            null, 
            wp.element.createElement(CanonicalCategory, null)
        );
    }
});
