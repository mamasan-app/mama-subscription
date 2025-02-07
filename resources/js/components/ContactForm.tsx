
import { Mail, Phone, MessageSquare, Facebook, Instagram, Linkedin, Twitter } from "lucide-react";

export const ContactForm = () => {
  return (
    <div className="py-20 bg-gray-50">
      <div className="container mx-auto px-6 max-w-4xl">
        <h2 className="text-3xl font-bold text-center mb-16">Contáctanos</h2>
        <div className="grid md:grid-cols-3 gap-12">
          <div>
            <h3 className="text-xl font-semibold mb-6">
              Información de contacto
            </h3>
            <div className="space-y-6">
              <div className="flex items-center gap-4">
                <Mail className="w-5 h-5 text-primary" />
                <span>soporte@cobrafacil.com</span>
              </div>
              <div className="flex items-center gap-4">
                <Phone className="w-5 h-5 text-primary" />
                <span>+58 (424) 123-4567</span>
              </div>
              <div className="flex items-center gap-4">
                <MessageSquare className="w-5 h-5 text-primary" />
                <span>Chat en vivo disponible</span>
              </div>
            </div>
          </div>

          <div>
            <h3 className="text-xl font-semibold mb-6">Horario</h3>
            <div className="space-y-4">
              <p className="text-gray-600">
                Lunes a Viernes: 8:00 AM - 6:00 PM
              </p>
              <p className="text-gray-600">
                Sábados: 9:00 AM - 1:00 PM
              </p>
            </div>
          </div>

          <div>
            <h3 className="text-xl font-semibold mb-6">Síguenos</h3>
            <div className="flex gap-6">
              <a 
                href="#" 
                className="text-primary hover:text-primary-dark transition-colors"
                aria-label="Facebook"
              >
                <Facebook className="w-6 h-6" />
              </a>
              <a 
                href="#" 
                className="text-primary hover:text-primary-dark transition-colors"
                aria-label="Instagram"
              >
                <Instagram className="w-6 h-6" />
              </a>
              <a 
                href="#" 
                className="text-primary hover:text-primary-dark transition-colors"
                aria-label="Twitter"
              >
                <Twitter className="w-6 h-6" />
              </a>
              <a 
                href="#" 
                className="text-primary hover:text-primary-dark transition-colors"
                aria-label="LinkedIn"
              >
                <Linkedin className="w-6 h-6" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
