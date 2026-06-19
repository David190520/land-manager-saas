# CLAUDE.md — Land Manager SaaS

## Descripción del proyecto
Sistema de gestión inmobiliaria SaaS para la venta de lotes/terrenos en Colombia.
Permite administrar proyectos, lotes, clientes, reservas, planes de pago y recaudos.
Cliente actual: Constructora La Cumbre S.A.S. (Flandes, Tolima).

## Stack técnico
- **Backend:** Laravel (PHP), PostgreSQL
- **Frontend:** Vue.js 3 + Vite 7 + Tailwind CSS
- **Autenticación:** Laravel Auth con roles
- **PDF:** DomPDF (Laravel)
- **Instalación:** `npm install --legacy-peer-deps` (conflicto conocido entre @vitejs/plugin-vue y Vite 7)

## Estructura de la BD (tablas principales)
- `tenants` — empresa/inquilino (arquitectura multi-tenant)
- `projects` — proyectos inmobiliarios
- `blocks` — manzanas dentro de un proyecto
- `lots` — lotes dentro de una manzana (status: available/reserved/sold/pending_approval)
- `clients` — compradores (campos: first_name, last_name, document_number, phone, phone_secondary)
- `reservations` — apartado de un lote por un cliente (down_payment = valor apartado)
- `payment_plans` — plan de cuotas asociado a una reserva
- `payments` — cuotas individuales del plan
- `documents` — repositorio de documentos notariales del cliente
- `audit_logs` — historial de cambios (entity_type + entity_id para filtrar por entidad)
- `internal_notifications` — centro de alertas interno
- `users` — usuarios del sistema (roles: admin, accountant, secretary)

## Flujo de negocio principal
Lote disponible → Reserva (apartado $500k) → Pago cuota inicial (30% del precio, plazo 1 mes) → Activación del contrato (payment_plan) → Pago de cuotas mensuales → Paz y Salvo PDF

## Convenciones del proyecto
- Toda query de búsqueda por nombre/documento usa `unaccent(LOWER(...))` para ignorar tildes y mayúsculas (extensión unaccent activada en PostgreSQL)
- Los PDFs se generan con DomPDF desde vistas Blade en `resources/views/reportes/`
- El historial de cambios se registra en `audit_logs` via Laravel Observers
- Las notificaciones se crean en `internal_notifications` con campo `dedup_key` para evitar duplicados
- Contexto Colombia: moneda COP, términos locales (Cédula de Ciudadanía, Cuota Inicial, Promesa de Compraventa, Soporte de Pago)

## Tareas pendientes (implementar en este orden)
1. ~~Search sin case sensitive ni tildes (ClientController + libro mayor)~~ ✓ DONE
2. Columna celular en libro mayor y detalle de contrato
3. Nuevo flujo de venta: separar apartado de cuota inicial (30%) en payment_plans
4. Descuento en precio del lote (campos en payment_plans: discount_type, discount_value, original_price)
5. Corrección PDF: mostrar cuota inicial correcta, no solo el apartado
6. Historial por lote usando audit_logs con entity_type='lot'
7. Roles de usuario: ajustar constraint de users.role a (admin, accountant, secretary)
8. Módulo de vendedores y comisiones (nueva tabla sellers + commissions)