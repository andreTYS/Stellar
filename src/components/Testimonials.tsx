import { Quote } from 'lucide-react'

const testimonials = [
  {
    name: 'Valentina Torres',
    role: 'Desarrolladora Frontend',
    avatar: 'VT',
    color: 'from-purple-500 to-pink-500',
    text: 'Stellar transformó mi carrera. El curso de React & Next.js me dio las bases para conseguir mi primer empleo tech en menos de 3 meses.',
  },
  {
    name: 'Sebastián Morales',
    role: 'Estudiante de Ingeniería',
    avatar: 'SM',
    color: 'from-blue-500 to-cyan-500',
    text: 'La combinación de teoría y proyectos prácticos es perfecta. Construí mi propio robot con Arduino siguiendo el curso paso a paso.',
  },
  {
    name: 'Camila Reyes',
    role: 'Diseñadora UX/UI',
    avatar: 'CR',
    color: 'from-pink-500 to-rose-500',
    text: 'Los cursos de diseño en Stellar son increíbles. La calidad del contenido y la comunidad me ayudaron a mejorar mi portafolio enormemente.',
  },
  {
    name: 'Diego Alvarez',
    role: 'Data Scientist',
    avatar: 'DA',
    color: 'from-green-500 to-teal-500',
    text: 'Estadística para Data Science fue el curso que me faltaba. La metodología práctica con Python es exactamente lo que necesitaba.',
  },
]

export default function Testimonials() {
  return (
    <section className="relative py-24 px-4">
      <div className="absolute top-1/2 left-0 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl pointer-events-none" />

      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-16">
          <p className="text-sm font-semibold text-pink-400 uppercase tracking-widest mb-3">
            Testimonios
          </p>
          <h2 className="text-4xl sm:text-5xl font-bold text-white mb-4">
            Lo que dicen{' '}
            <span className="gradient-text-warm">nuestros estudiantes</span>
          </h2>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
          {testimonials.map(({ name, role, avatar, color, text }) => (
            <div
              key={name}
              className="glass rounded-2xl p-6 card-hover border border-white/5 hover:border-white/10"
            >
              <Quote size={28} className="text-purple-400/40 mb-4" />
              <p className="text-white/70 text-sm leading-relaxed mb-6">{text}</p>
              <div className="flex items-center gap-3">
                <div
                  className={`w-10 h-10 rounded-full bg-gradient-to-br ${color} flex items-center justify-center text-white text-xs font-bold flex-shrink-0`}
                >
                  {avatar}
                </div>
                <div>
                  <p className="text-white font-semibold text-sm">{name}</p>
                  <p className="text-white/40 text-xs">{role}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
