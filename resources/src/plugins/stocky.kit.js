import BootstrapVue from 'bootstrap-vue/dist/bootstrap-vue.esm';
import VueGoodTablePlugin from "vue-good-table";
import Meta from "vue-meta";
import "./../assets/styles/sass/themes/lite-purple.scss";
import "./sweetalert2.js";
import VueHtmlToPaper from 'vue-html-to-paper';
const options = {
  name: '_blank',
  specs: [
    'fullscreen=yes',
    'titlebar=yes',
    'scrollbars=yes'
  ],
  styles: [
    'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css',
    'https://unpkg.com/kidlat-css/css/kidlat.css',    
  ],
  timeout: 1000, // default timeout before the print window appears
  autoClose: true, // if false, the window will not close after printing
  windowTitle: window.document.title, // override the window title
}


export default {
  install(Vue) {
    Vue.use(BootstrapVue);
    Vue.component(
      "large-sidebar",
      // The `import` function returns a Promise.
      () => import(/* webpackChunkName: "largeSidebar" */ "../containers/layouts/largeSidebar")
    );
 
    Vue.component(
      "customizer",
      // The `import` function returns a Promise.
      () => import(/* webpackChunkName: "customizer" */ "../components/common/customizer.vue")
    );
    Vue.component("vue-perfect-scrollbar", () =>
      import(/* webpackChunkName: "vue-perfect-scrollbar" */ "vue-perfect-scrollbar")
    );
    Vue.use(Meta, {
      keyName: "metaInfo",
      attribute: "data-vue-meta",
      ssrAttribute: "data-vue-meta-server-rendered",
      tagIDKeyName: "vmid",
      refreshOnceOnNavigation: true
    });
    Vue.use(VueGoodTablePlugin);
    Vue.use(VueHtmlToPaper, options);


  }
};
