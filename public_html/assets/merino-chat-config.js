/**
 * Información pública editable. No pongas contraseñas ni datos de pacientes aquí.
 * Los precios y los exámenes se consultan en api/catalogo.php, nunca en este archivo.
 */
export const MERINO_CHAT_CONFIG = Object.freeze({
  catalogUrl: '/api/catalogo.php',
  name: 'Laboratorio Clínico Merino',
  phone: '+503 2606 6596',
  phoneUrl: 'tel:+50326066596',
  whatsapp: '+503 7595 4210',
  whatsappUrl: 'https://wa.me/50375954210',
  email: 'lab.merino20@gmail.com',
  address: '4ª Calle Oriente #6, Barrio San Francisco, San Vicente, El Salvador.',
  mapUrl: 'https://www.waze.com/en/live-map/directions/sv/san-vicente/san-vicente/laboratorio-clinico-merino?place=ChIJV1GRqPepZI8RUHPD4B_UIgo',
  socialLinks: [
    { label: 'Facebook: Laboratorio Clínico Merino', url: 'https://www.facebook.com/LaboratorioClinicoMerino' },
    { label: 'Instagram: @lab.merino', url: 'https://www.instagram.com/lab.merino/?hl=es' }
  ],
  about: [
    {
      label: 'Nuestro propósito',
      text: 'En Laboratorio Clínico Merino trabajamos con ciencia, ética y vocación humana para cuidar lo más importante: tu salud.'
    },
    {
      label: 'Misión',
      text: 'Brindar servicios de diagnóstico clínico confiables, precisos y oportunos, apoyados en tecnología moderna y personal capacitado, garantizando resultados de calidad con un trato humano, responsable y ético para cada paciente.'
    },
    {
      label: 'Visión',
      text: 'Ser el laboratorio clínico de referencia en la región, reconocido por la excelencia científica, la innovación constante y la confianza de nuestros pacientes, contribuyendo al bienestar integral de la comunidad.'
    }
  ],
  hours: [
    'Lunes a viernes: 6:00 a. m. a 5:00 p. m.',
    'Sábado: 6:00 a. m. a 12:00 p. m.',
    'Domingo: 7:00 a. m. a 12:00 p. m.'
  ],
  services: [
    'Hematología y coagulación',
    'Química sanguínea',
    'Inmunología',
    'Hormonas',
    'Bacteriología y cultivos',
    'Uroanálisis y coprología'
  ],
  homeServiceSteps: [
    'Escríbenos por WhatsApp para solicitar el servicio y cotizarlo.',
    'Coordina con el laboratorio la ubicación, el día y la hora.',
    'El laboratorio realiza la toma de muestras a domicilio.',
    'Los resultados de este servicio se envían por WhatsApp o correo.'
  ],
  questions: [
    { label: 'Precios de exámenes', query: 'Lista de exámenes y precios' },
    { label: 'Perfiles', query: 'Lista de perfiles y precios' },
    { label: 'Horarios', query: 'Horarios' },
    { label: 'Ubicación', query: 'Ubicación' },
    { label: 'A domicilio', query: 'Servicio a domicilio' },
    { label: 'Contacto', query: 'Contacto' },
    { label: 'Resultados', query: 'Resultados' },
    { label: 'Preparación', query: 'Preparación para un examen' },
    { label: 'Pagos y seguros', query: 'Formas de pago y seguros' },
    { label: 'Servicios', query: 'Servicios' },
    { label: 'Redes sociales', query: 'Redes sociales' },
    { label: 'Nosotros', query: 'Nosotros' }
  ],
  pageSize: 8
});
