import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '../views/HomePage.vue'
import LoginPage from '../views/LoginPage.vue'
import RegisterPage from '../views/RegisterPage.vue'
import InvoicePayPage from '../views/InvoicePayPage.vue'
import PaymentRedirectPage from '../views/PaymentRedirectPage.vue'
import NotFoundPage from '../views/NotFoundPage.vue'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: HomePage },
    { path: '/login', name: 'login', component: LoginPage },
    { path: '/register', name: 'register', component: RegisterPage },
    {
      path: '/invoices/:number/pay',
      name: 'invoice-pay',
      component: InvoicePayPage,
      props: true,
      meta: { requiresAuth: true },
    },
    { path: '/payments/redirect', name: 'payment-redirect', component: PaymentRedirectPage },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundPage },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.name === 'login' && auth.token) {
    const redirect = typeof to.query.redirect === 'string' ? to.query.redirect : '/'
    return redirect
  }

  if (to.name === 'register' && auth.token) {
    const redirect = typeof to.query.redirect === 'string' ? to.query.redirect : '/'
    return redirect
  }

  if (to.meta?.requiresAuth && !auth.token) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
})

export default router
