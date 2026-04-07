'use client'

import { useState } from 'react'
import { Send, Mail, MapPin, Twitter, Github, Linkedin } from 'lucide-react'

export default function Contact() {
  const [form, setForm] = useState({ name: '', email: '', message: '' })
  const [sent, setSent] = useState(false)

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    // Simulate send
    setSent(true)
    setForm({ name: '', email: '', message: '' })
    setTimeout(() => setSent(false), 4000)
  }

  return (
    <section id="contact" className="relative py-24 px-4">
      <div className="absolute bottom-0 right-0 w-80 h-80 bg-cyan-600/10 rounded-full blur-3xl pointer-events-none" />

      <div className="max-w-5xl mx-auto">
        <div className="text-center mb-16">
          <p className="text-sm font-semibold text-cyan-400 uppercase tracking-widest mb-3">
            Contacto
          </p>
          <h2 className="text-4xl sm:text-5xl font-bold text-white mb-4">
            Hablemos del{' '}
            <span className="gradient-text">futuro</span>
          </h2>
          <p className="text-white/50 max-w-md mx-auto">
            ¿Tienes preguntas sobre nuestros cursos? ¿Quieres colaborar? Escríbenos.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-5 gap-10">
          {/* Info */}
          <div className="lg:col-span-2 flex flex-col gap-6">
            <div className="glass rounded-2xl p-5 flex items-start gap-4 border border-white/5">
              <Mail size={20} className="text-purple-400 mt-0.5 flex-shrink-0" />
              <div>
                <p className="text-white font-medium text-sm mb-1">Email</p>
                <p className="text-white/50 text-sm">hola@stellar.edu</p>
              </div>
            </div>
            <div className="glass rounded-2xl p-5 flex items-start gap-4 border border-white/5">
              <MapPin size={20} className="text-cyan-400 mt-0.5 flex-shrink-0" />
              <div>
                <p className="text-white font-medium text-sm mb-1">Ubicación</p>
                <p className="text-white/50 text-sm">Latinoamérica — 100% en línea</p>
              </div>
            </div>

            {/* Social */}
            <div className="glass rounded-2xl p-5 border border-white/5">
              <p className="text-white font-medium text-sm mb-4">Síguenos</p>
              <div className="flex gap-3">
                {[
                  { icon: Twitter,  label: 'Twitter' },
                  { icon: Github,   label: 'GitHub' },
                  { icon: Linkedin, label: 'LinkedIn' },
                ].map(({ icon: Icon, label }) => (
                  <button
                    key={label}
                    aria-label={label}
                    className="p-2.5 rounded-xl glass border border-white/10 text-white/60 hover:text-white hover:border-purple-500/50 transition-all duration-200 hover:scale-110"
                  >
                    <Icon size={18} />
                  </button>
                ))}
              </div>
            </div>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="lg:col-span-3 glass rounded-2xl p-6 border border-white/5 flex flex-col gap-4">
            {sent && (
              <div className="rounded-xl bg-green-500/10 border border-green-500/30 text-green-400 text-sm px-4 py-3 text-center">
                Mensaje enviado. ¡Te responderemos pronto!
              </div>
            )}

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs text-white/50 mb-1.5 uppercase tracking-wide">Nombre</label>
                <input
                  type="text"
                  required
                  value={form.name}
                  onChange={(e) => setForm({ ...form, name: e.target.value })}
                  placeholder="Tu nombre"
                  className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-white/30 focus:outline-none focus:border-purple-500/60 transition-colors"
                />
              </div>
              <div>
                <label className="block text-xs text-white/50 mb-1.5 uppercase tracking-wide">Email</label>
                <input
                  type="email"
                  required
                  value={form.email}
                  onChange={(e) => setForm({ ...form, email: e.target.value })}
                  placeholder="tu@email.com"
                  className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-white/30 focus:outline-none focus:border-purple-500/60 transition-colors"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs text-white/50 mb-1.5 uppercase tracking-wide">Mensaje</label>
              <textarea
                required
                rows={5}
                value={form.message}
                onChange={(e) => setForm({ ...form, message: e.target.value })}
                placeholder="Cuéntanos en qué podemos ayudarte..."
                className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-white/30 focus:outline-none focus:border-purple-500/60 transition-colors resize-none"
              />
            </div>

            <button type="submit" className="btn-primary justify-center mt-2">
              Enviar mensaje
              <Send size={16} />
            </button>
          </form>
        </div>
      </div>
    </section>
  )
}
